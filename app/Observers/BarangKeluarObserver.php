<?php

namespace App\Observers;

use App\Models\BarangKeluar;
use App\Jobs\CalculateOpnameJob;
use App\Jobs\CalculateStatusTempatJob;
use Illuminate\Support\Facades\Log;

class BarangKeluarObserver
{
    /**
     * Handle the BarangKeluar "created" event.
     */
    public function created(BarangKeluar $barangKeluar)
    {
        // Dispatch job untuk kalkulasi opname barang terkait
        CalculateOpnameJob::dispatch($barangKeluar->barang_kode)
            ->delay(now()->addSeconds(5))
            ->onQueue('calculations');

        // Dispatch job untuk kalkulasi status tempat terkait
        if ($barangKeluar->tempat_id) {
            CalculateStatusTempatJob::dispatch($barangKeluar->tempat_id)
                ->delay(now()->addSeconds(5))
                ->onQueue('calculations');
        }

        Log::info("Job kalkulasi opname & status tempat di-dispatch untuk barang {$barangKeluar->barang_kode} dan tempat {$barangKeluar->tempat_id} (dari barang keluar created)");
    }

    /**
     * Handle the BarangKeluar "updated" event.
     */
    public function updated(BarangKeluar $barangKeluar)
    {
        // Jika barang_kode berubah, kalkulasi kedua barang (lama & baru)
        if ($barangKeluar->isDirty('barang_kode')) {
            CalculateOpnameJob::dispatch($barangKeluar->getOriginal('barang_kode'))
                ->delay(now()->addSeconds(5))
                ->onQueue('calculations');
            
            CalculateOpnameJob::dispatch($barangKeluar->barang_kode)
                ->delay(now()->addSeconds(5))
                ->onQueue('calculations');
        } else {
            CalculateOpnameJob::dispatch($barangKeluar->barang_kode)
                ->delay(now()->addSeconds(5))
                ->onQueue('calculations');
        }

        // Jika tempat_id berubah, kalkulasi kedua tempat (lama & baru)
        if ($barangKeluar->isDirty('tempat_id')) {
            $oldTempatId = $barangKeluar->getOriginal('tempat_id');
            $newTempatId = $barangKeluar->tempat_id;
            
            if ($oldTempatId) {
                CalculateStatusTempatJob::dispatch($oldTempatId)
                    ->delay(now()->addSeconds(5))
                    ->onQueue('calculations');
            }
            
            if ($newTempatId) {
                CalculateStatusTempatJob::dispatch($newTempatId)
                    ->delay(now()->addSeconds(5))
                    ->onQueue('calculations');
            }

            Log::info("Job kalkulasi status tempat di-dispatch untuk tempat {$oldTempatId} dan {$newTempatId} (dari barang keluar updated)");
        } else {
            if ($barangKeluar->tempat_id) {
                CalculateStatusTempatJob::dispatch($barangKeluar->tempat_id)
                    ->delay(now()->addSeconds(5))
                    ->onQueue('calculations');
            }
        }

        Log::info("Job kalkulasi opname & status tempat di-dispatch untuk barang {$barangKeluar->barang_kode} (dari barang keluar updated)");
    }

    /**
     * Handle the BarangKeluar "deleted" event.
     */
    public function deleted(BarangKeluar $barangKeluar)
    {
        // Dispatch job untuk kalkulasi opname barang terkait
        CalculateOpnameJob::dispatch($barangKeluar->barang_kode)
            ->delay(now()->addSeconds(5))
            ->onQueue('calculations');

        // Dispatch job untuk kalkulasi status tempat terkait
        if ($barangKeluar->tempat_id) {
            CalculateStatusTempatJob::dispatch($barangKeluar->tempat_id)
                ->delay(now()->addSeconds(5))
                ->onQueue('calculations');
        }

        Log::info("Job kalkulasi opname & status tempat di-dispatch untuk barang {$barangKeluar->barang_kode} dan tempat {$barangKeluar->tempat_id} (dari barang keluar deleted)");
    }
}
