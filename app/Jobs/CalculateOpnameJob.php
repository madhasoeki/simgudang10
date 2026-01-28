<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\Opname;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateOpnameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $barangKode;

    /**
     * Create a new job instance.
     */
    public function __construct($barangKode)
    {
        $this->barangKode = $barangKode;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            $barang = Barang::where('kode', $this->barangKode)->first();
            
            if (!$barang) {
                Log::warning("Barang dengan kode {$this->barangKode} tidak ditemukan");
                return;
            }

            $today = Carbon::today();

            // Tentukan periode opname saat ini
            if ($today->day >= 26) {
                $periodeAwalSaatIni = $today->copy()->day(26);
                $periodeAkhirSaatIni = $today->copy()->addMonth()->day(25);
            } else {
                $periodeAwalSaatIni = $today->copy()->subMonth()->day(26);
                $periodeAkhirSaatIni = $today->copy()->day(25);
            }

            // Periode sebelumnya untuk stok awal
            $periodeAkhirSebelumnya = $periodeAwalSaatIni->copy()->subDay();
            $periodeAwalSebelumnya = $periodeAkhirSebelumnya->copy()->day(26)->subMonth();

            $opnameSebelumnya = Opname::where('barang_kode', $this->barangKode)
                ->where('periode_awal', $periodeAwalSebelumnya->toDateString())
                ->where('periode_akhir', $periodeAkhirSebelumnya->toDateString())
                ->where('approved', true)
                ->first();
            
            $stockAwal = $opnameSebelumnya ? $opnameSebelumnya->total_lapangan : 0;

            // Hitung total masuk dan keluar untuk periode saat ini
            $totalMasuk = BarangMasuk::where('barang_kode', $this->barangKode)
                ->whereBetween('tanggal', [$periodeAwalSaatIni, $periodeAkhirSaatIni])
                ->sum('qty');

            $totalKeluar = BarangKeluar::where('barang_kode', $this->barangKode)
                ->whereBetween('tanggal', [$periodeAwalSaatIni, $periodeAkhirSaatIni])
                ->sum('qty');

            $stockTotal = $stockAwal + $totalMasuk - $totalKeluar;

            // Cek apakah sudah ada opname untuk periode ini
            $existingOpname = Opname::where('barang_kode', $this->barangKode)
                ->where('periode_awal', $periodeAwalSaatIni->toDateString())
                ->where('periode_akhir', $periodeAkhirSaatIni->toDateString())
                ->first();

            // Hanya update jika belum approved
            if (!$existingOpname || !$existingOpname->approved) {
                Opname::updateOrCreate(
                    [
                        'barang_kode' => $this->barangKode,
                        'periode_awal' => $periodeAwalSaatIni->toDateString(),
                        'periode_akhir' => $periodeAkhirSaatIni->toDateString(),
                    ],
                    [
                        'stock_awal' => $stockAwal,
                        'total_masuk' => $totalMasuk,
                        'total_keluar' => $totalKeluar,
                        'stock_total' => $stockTotal,
                        'total_lapangan' => $existingOpname && !$existingOpname->approved ? $existingOpname->total_lapangan : 0,
                        'selisih' => $existingOpname && !$existingOpname->approved ? ($stockTotal - $existingOpname->total_lapangan) : ($stockTotal - 0),
                        'keterangan' => $existingOpname && !$existingOpname->approved ? $existingOpname->keterangan : null,
                    ]
                );

                Log::info("Opname untuk barang {$this->barangKode} berhasil dikalkulasi (periode: {$periodeAwalSaatIni->toDateString()} - {$periodeAkhirSaatIni->toDateString()})");
            } else {
                Log::info("Opname untuk barang {$this->barangKode} sudah approved, tidak perlu dikalkulasi ulang");
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error calculating opname untuk barang {$this->barangKode}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60];
}
