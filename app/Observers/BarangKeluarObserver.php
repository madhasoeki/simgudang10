<?php

namespace App\Observers;

use App\Jobs\CalculateOpnameJob;
use App\Jobs\CalculateStatusTempatJob;
use App\Models\BarangKeluar;
use Illuminate\Support\Facades\Log;

class BarangKeluarObserver
{
    private const CALCULATION_QUEUE = 'calculations';

    private const DISPATCH_DELAY_SECONDS = 5;

    /**
     * Handle the BarangKeluar "created" event.
     */
    public function created(BarangKeluar $barangKeluar): void
    {
        $this->dispatchOpname($barangKeluar->barang_kode);
        $this->dispatchStatusTempatIfPresent($barangKeluar->tempat_id);

        Log::info('Job kalkulasi opname & status tempat di-dispatch (barang keluar created).', [
            'barang_kode' => $barangKeluar->barang_kode,
            'tempat_id' => $barangKeluar->tempat_id,
        ]);
    }

    /**
     * Handle the BarangKeluar "updated" event.
     */
    public function updated(BarangKeluar $barangKeluar): void
    {
        $barangCodes = [$barangKeluar->barang_kode];
        if ($barangKeluar->wasChanged('barang_kode')) {
            $barangCodes[] = $barangKeluar->getOriginal('barang_kode');
        }

        $tempatIds = [$barangKeluar->tempat_id];
        if ($barangKeluar->wasChanged('tempat_id')) {
            $tempatIds[] = $barangKeluar->getOriginal('tempat_id');
        }

        $this->dispatchUniqueOpnames($barangCodes);
        $this->dispatchUniqueStatusTempat($tempatIds);

        Log::info('Job kalkulasi opname & status tempat di-dispatch (barang keluar updated).', [
            'barang_kode' => array_values(array_filter(array_unique($barangCodes))),
            'tempat_id' => array_values(array_filter(array_unique($tempatIds), static fn ($value) => ! is_null($value))),
        ]);
    }

    private function dispatchUniqueOpnames(array $barangCodes): void
    {
        foreach (array_values(array_filter(array_unique($barangCodes))) as $barangKode) {
            $this->dispatchOpname($barangKode);
        }
    }

    /**
     * Handle the BarangKeluar "deleted" event.
     */
    public function deleted(BarangKeluar $barangKeluar): void
    {
        $this->dispatchOpname($barangKeluar->barang_kode);
        $this->dispatchStatusTempatIfPresent($barangKeluar->tempat_id);

        Log::info('Job kalkulasi opname & status tempat di-dispatch (barang keluar deleted).', [
            'barang_kode' => $barangKeluar->barang_kode,
            'tempat_id' => $barangKeluar->tempat_id,
        ]);
    }

    private function dispatchUniqueStatusTempat(array $tempatIds): void
    {
        $filtered = array_values(array_filter(array_unique($tempatIds), static fn ($value) => ! is_null($value) && (int) $value > 0));
        foreach ($filtered as $tempatId) {
            $this->dispatchStatusTempat((int) $tempatId);
        }
    }

    private function dispatchStatusTempatIfPresent(?int $tempatId): void
    {
        if ($tempatId) {
            $this->dispatchStatusTempat($tempatId);
        }
    }

    private function dispatchStatusTempat(int $tempatId): void
    {
        CalculateStatusTempatJob::dispatch($tempatId)
            ->delay(now()->addSeconds(self::DISPATCH_DELAY_SECONDS))
            ->onQueue(self::CALCULATION_QUEUE);
    }

    private function dispatchOpname(string $barangKode): void
    {
        CalculateOpnameJob::dispatch($barangKode)
            ->delay(now()->addSeconds(self::DISPATCH_DELAY_SECONDS))
            ->onQueue(self::CALCULATION_QUEUE);
    }
}
