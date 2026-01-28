<?php

namespace App\Observers;

use App\Models\BarangMasuk;
use App\Jobs\CalculateOpnameJob;
use Illuminate\Support\Facades\Log;

class BarangMasukObserver
{
    /**
     * Handle the BarangMasuk "created" event.
     */
    public function created(BarangMasuk $barangMasuk)
    {
        // Dispatch job untuk kalkulasi opname barang terkait
        CalculateOpnameJob::dispatch($barangMasuk->barang_kode)
            ->delay(now()->addSeconds(5))
            ->onQueue('calculations');

        Log::info("Job kalkulasi opname di-dispatch untuk barang {$barangMasuk->barang_kode} (dari barang masuk created)");
    }

    /**
     * Handle the BarangMasuk "updated" event.
     */
    public function updated(BarangMasuk $barangMasuk)
    {
        // Jika barang_kode berubah, kalkulasi kedua barang (lama & baru)
        if ($barangMasuk->isDirty('barang_kode')) {
            CalculateOpnameJob::dispatch($barangMasuk->getOriginal('barang_kode'))
                ->delay(now()->addSeconds(5))
                ->onQueue('calculations');
            
            CalculateOpnameJob::dispatch($barangMasuk->barang_kode)
                ->delay(now()->addSeconds(5))
                ->onQueue('calculations');

            Log::info("Job kalkulasi opname di-dispatch untuk barang {$barangMasuk->getOriginal('barang_kode')} dan {$barangMasuk->barang_kode} (dari barang masuk updated)");
        } else {
            CalculateOpnameJob::dispatch($barangMasuk->barang_kode)
                ->delay(now()->addSeconds(5))
                ->onQueue('calculations');

            Log::info("Job kalkulasi opname di-dispatch untuk barang {$barangMasuk->barang_kode} (dari barang masuk updated)");
        }
    }

    /**
     * Handle the BarangMasuk "deleted" event.
     */
    public function deleted(BarangMasuk $barangMasuk)
    {
        // Dispatch job untuk kalkulasi opname barang terkait
        CalculateOpnameJob::dispatch($barangMasuk->barang_kode)
            ->delay(now()->addSeconds(5))
            ->onQueue('calculations');

        Log::info("Job kalkulasi opname di-dispatch untuk barang {$barangMasuk->barang_kode} (dari barang masuk deleted)");
    }
}
