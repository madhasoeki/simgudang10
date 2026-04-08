<?php

namespace App\Jobs;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\Opname;
use App\Support\ReportingPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateOpnameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $barangKode;

    /**
     * Create a new job instance.
     */
    public function __construct(string $barangKode)
    {
        $this->barangKode = $barangKode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::transaction(function (): void {
                $barang = Barang::where('kode', $this->barangKode)->first();
                if (! $barang) {
                    Log::warning('Barang tidak ditemukan saat kalkulasi opname.', [
                        'barang_kode' => $this->barangKode,
                    ]);

                    return;
                }

                $period = ReportingPeriod::resolve();
                $periodeAwalSaatIni = $period['current_start']->toDateString();
                $periodeAkhirSaatIni = $period['current_end']->toDateString();

                $existingOpname = Opname::where('barang_kode', $this->barangKode)
                    ->where('periode_awal', $periodeAwalSaatIni)
                    ->where('periode_akhir', $periodeAkhirSaatIni)
                    ->first();

                if ($existingOpname && $existingOpname->approved) {
                    Log::info('Opname sudah approved, kalkulasi dilewati.', [
                        'barang_kode' => $this->barangKode,
                        'periode_awal' => $periodeAwalSaatIni,
                        'periode_akhir' => $periodeAkhirSaatIni,
                    ]);

                    return;
                }

                $stockAwal = $this->resolveStockAwal($period['previous_start']->toDateString(), $period['previous_end']->toDateString());
                $totalMasuk = $this->resolveTotalMasuk($periodeAwalSaatIni, $periodeAkhirSaatIni);
                $totalKeluar = $this->resolveTotalKeluar($periodeAwalSaatIni, $periodeAkhirSaatIni);
                $stockTotal = $stockAwal + $totalMasuk - $totalKeluar;

                $totalLapangan = $existingOpname ? (int) $existingOpname->total_lapangan : 0;
                $keterangan = $existingOpname ? $existingOpname->keterangan : null;

                Opname::updateOrCreate(
                    [
                        'barang_kode' => $this->barangKode,
                        'periode_awal' => $periodeAwalSaatIni,
                        'periode_akhir' => $periodeAkhirSaatIni,
                    ],
                    [
                        'stock_awal' => $stockAwal,
                        'total_masuk' => $totalMasuk,
                        'total_keluar' => $totalKeluar,
                        'stock_total' => $stockTotal,
                        'total_lapangan' => $totalLapangan,
                        'selisih' => $totalLapangan - $stockTotal,
                        'keterangan' => $keterangan,
                    ]
                );

                Log::info('Opname berhasil dikalkulasi.', [
                    'barang_kode' => $this->barangKode,
                    'periode_awal' => $periodeAwalSaatIni,
                    'periode_akhir' => $periodeAkhirSaatIni,
                    'stock_awal' => $stockAwal,
                    'total_masuk' => $totalMasuk,
                    'total_keluar' => $totalKeluar,
                    'stock_total' => $stockTotal,
                ]);
            });
        } catch (\Throwable $exception) {
            Log::error('Kalkulasi opname gagal.', [
                'barang_kode' => $this->barangKode,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function resolveStockAwal(string $periodeAwalSebelumnya, string $periodeAkhirSebelumnya): int
    {
        $opnameSebelumnya = Opname::where('barang_kode', $this->barangKode)
            ->where('periode_awal', $periodeAwalSebelumnya)
            ->where('periode_akhir', $periodeAkhirSebelumnya)
            ->where('approved', true)
            ->first();

        return $opnameSebelumnya ? (int) $opnameSebelumnya->total_lapangan : 0;
    }

    private function resolveTotalMasuk(string $periodeAwal, string $periodeAkhir): int
    {
        return (int) BarangMasuk::where('barang_kode', $this->barangKode)
            ->whereBetween('tanggal', [$periodeAwal, $periodeAkhir])
            ->sum('qty');
    }

    private function resolveTotalKeluar(string $periodeAwal, string $periodeAkhir): int
    {
        return (int) BarangKeluar::where('barang_kode', $this->barangKode)
            ->whereBetween('tanggal', [$periodeAwal, $periodeAkhir])
            ->sum('qty');
    }

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [10, 30, 60];
}
