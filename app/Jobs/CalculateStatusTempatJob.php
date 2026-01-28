<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Tempat;
use App\Models\StatusTempat;
use App\Models\BarangKeluar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateStatusTempatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tempatId;

    /**
     * Create a new job instance.
     */
    public function __construct($tempatId)
    {
        $this->tempatId = $tempatId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            $tempat = Tempat::find($this->tempatId);
            
            if (!$tempat) {
                Log::warning("Tempat ID {$this->tempatId} tidak ditemukan");
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

            // Hitung total jumlah harga barang keluar untuk tempat dan periode ini
            $totalBarangKeluar = BarangKeluar::where('tempat_id', $this->tempatId)
                ->whereBetween('tanggal', [$periodeAwalSaatIni, $periodeAkhirSaatIni])
                ->sum(DB::raw('qty * harga'));

            $statusTempat = StatusTempat::firstOrNew([
                'tempat_id' => $this->tempatId,
                'periode_awal' => $periodeAwalSaatIni->toDateString(),
                'periode_akhir' => $periodeAkhirSaatIni->toDateString(),
            ]);

            // Update total dan set status ke 'loading' jika statusnya belum 'done'
            if ($statusTempat->status !== 'done') {
                $statusTempat->total = $totalBarangKeluar ?? 0;
                $statusTempat->status = 'loading';
                $statusTempat->save();
                
                Log::info("Status tempat ID {$this->tempatId} ({$tempat->nama}) berhasil dikalkulasi. Total: {$statusTempat->total}");
            } else {
                // Jika sudah done, hanya update totalnya saja
                if ($statusTempat->exists) {
                    $statusTempat->total = $totalBarangKeluar ?? 0;
                    $statusTempat->save();
                    
                    Log::info("Status tempat ID {$this->tempatId} ({$tempat->nama}) diperbarui (status tetap 'done'). Total: {$statusTempat->total}");
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error calculating status tempat ID {$this->tempatId}: " . $e->getMessage());
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
