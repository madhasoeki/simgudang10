<?php

namespace App\Observers;

use App\Jobs\CalculateOpnameJob;
use App\Models\BarangMasuk;
use Illuminate\Support\Facades\Log;

class BarangMasukObserver
{
    private const CALCULATION_QUEUE = 'calculations';

    private const DISPATCH_DELAY_SECONDS = 5;

    /**
     * Handle the BarangMasuk "created" event.
     */
    public function created(BarangMasuk $barangMasuk): void
    {
        $this->dispatchOpname($barangMasuk->barang_kode);

        Log::info('Job kalkulasi opname di-dispatch (barang masuk created).', [
            'barang_kode' => $barangMasuk->barang_kode,
        ]);
    }

    /**
     * Handle the BarangMasuk "updated" event.
     */
    public function updated(BarangMasuk $barangMasuk): void
    {
        $barangCodes = [$barangMasuk->barang_kode];
        if ($barangMasuk->wasChanged('barang_kode')) {
            $barangCodes[] = $barangMasuk->getOriginal('barang_kode');
        }

        $this->dispatchUniqueOpnames($barangCodes);

        Log::info('Job kalkulasi opname di-dispatch (barang masuk updated).', [
            'barang_kode' => array_values(array_filter(array_unique($barangCodes))),
        ]);
    }

    /**
     * Handle the BarangMasuk "deleted" event.
     */
    public function deleted(BarangMasuk $barangMasuk): void
    {
        $this->dispatchOpname($barangMasuk->barang_kode);

        Log::info('Job kalkulasi opname di-dispatch (barang masuk deleted).', [
            'barang_kode' => $barangMasuk->barang_kode,
        ]);
    }

    private function dispatchUniqueOpnames(array $barangCodes): void
    {
        foreach (array_values(array_filter(array_unique($barangCodes))) as $barangKode) {
            $this->dispatchOpname($barangKode);
        }
    }

    private function dispatchOpname(string $barangKode): void
    {
        CalculateOpnameJob::dispatch($barangKode)
            ->delay(now()->addSeconds(self::DISPATCH_DELAY_SECONDS))
            ->onQueue(self::CALCULATION_QUEUE);
    }
}
