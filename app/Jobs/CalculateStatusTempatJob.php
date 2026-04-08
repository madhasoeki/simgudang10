<?php

namespace App\Jobs;

use App\Models\BarangKeluar;
use App\Models\StatusTempat;
use App\Models\Tempat;
use App\Support\ReportingPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateStatusTempatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tempatId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $tempatId)
    {
        $this->tempatId = $tempatId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::transaction(function (): void {
                $tempat = Tempat::find($this->tempatId);
                if (! $tempat) {
                    Log::warning('Tempat tidak ditemukan saat kalkulasi status.', [
                        'tempat_id' => $this->tempatId,
                    ]);

                    return;
                }

                $period = ReportingPeriod::resolve();
                $periodeAwalSaatIni = $period['current_start']->toDateString();
                $periodeAkhirSaatIni = $period['current_end']->toDateString();

                $totalBarangKeluar = (int) BarangKeluar::where('tempat_id', $this->tempatId)
                    ->whereBetween('tanggal', [$periodeAwalSaatIni, $periodeAkhirSaatIni])
                    ->sum(DB::raw('qty * harga'));

                $statusTempat = StatusTempat::firstOrNew([
                    'tempat_id' => $this->tempatId,
                    'periode_awal' => $periodeAwalSaatIni,
                    'periode_akhir' => $periodeAkhirSaatIni,
                ]);

                $statusTempat->total = $totalBarangKeluar;
                if ($statusTempat->status !== 'done') {
                    $statusTempat->status = 'loading';
                }

                $statusTempat->save();

                Log::info('Status tempat berhasil dikalkulasi.', [
                    'tempat_id' => $this->tempatId,
                    'tempat_nama' => $tempat->nama,
                    'periode_awal' => $periodeAwalSaatIni,
                    'periode_akhir' => $periodeAkhirSaatIni,
                    'status' => $statusTempat->status,
                    'total' => $statusTempat->total,
                ]);
            });
        } catch (\Throwable $exception) {
            Log::error('Kalkulasi status tempat gagal.', [
                'tempat_id' => $this->tempatId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
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
