<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\Opname;
use Illuminate\Support\Facades\Log;

class GenerateOpnameReport extends Command
{
    protected $signature = 'opname:generate';
    protected $description = 'Generate or update opname records for the current opname period';

    public function handle()
    {
        $this->info('Starting to generate opname report...');

        $today = Carbon::today(); // Misal hari ini 29 Mei 2025

        if ($today->day >= 26) {
            // Jika hari ini tanggal 26 atau lebih, periode saat ini dimulai dari tgl 26 bulan ini
            // hingga tgl 25 bulan depan.
            $periodeAwalSaatIni = $today->copy()->day(26);
            $periodeAkhirSaatIni = $today->copy()->addMonth()->day(25);
        } else {
            // Jika hari ini sebelum tanggal 26, periode saat ini dimulai dari tgl 26 bulan lalu
            // hingga tgl 25 bulan ini.
            $periodeAwalSaatIni = $today->copy()->subMonth()->day(26);
            $periodeAkhirSaatIni = $today->copy()->day(25);
        }
        
        $this->info("Processing for current opname period: {$periodeAwalSaatIni->toDateString()} to {$periodeAkhirSaatIni->toDateString()}");

        $barangs = Barang::all();

        foreach ($barangs as $barang) {
            // --- Menentukan Periode Opname SEBELUMNYA untuk Stok Awal ---
            // Periode sebelumnya adalah satu bulan sebelum periodeAwalSaatIni.
            $periodeAkhirSebelumnya = $periodeAwalSaatIni->copy()->subDay(); // Misal, jika periodeAwalSaatIni adalah 26 Mei, maka ini 25 Mei
            $periodeAwalSebelumnya = $periodeAkhirSebelumnya->copy()->day(26)->subMonth(); // Maka ini 26 April

            $opnameSebelumnya = Opname::where('barang_kode', $barang->kode)
                ->where('periode_awal', $periodeAwalSebelumnya->toDateString())
                ->where('periode_akhir', $periodeAkhirSebelumnya->toDateString())
                ->where('approved', true)
                ->first();
            
            $stockAwal = $opnameSebelumnya ? $opnameSebelumnya->total_lapangan : 0;

            // Hitung total masuk dan keluar untuk PERIODE SAAT INI
            $totalMasuk = BarangMasuk::where('barang_kode', $barang->kode)
                ->whereBetween('tanggal', [$periodeAwalSaatIni, $periodeAkhirSaatIni]) // Gunakan periode saat ini
                ->sum('qty');

            $totalKeluar = BarangKeluar::where('barang_kode', $barang->kode)
                ->whereBetween('tanggal', [$periodeAwalSaatIni, $periodeAkhirSaatIni]) // Gunakan periode saat ini
                ->sum('qty');

            $stockTotal = $stockAwal + $totalMasuk - $totalKeluar;

            // Update atau buat data opname untuk PERIODE SAAT INI
            $existingOpname = Opname::where('barang_kode', $barang->kode)
                ->where('periode_awal', $periodeAwalSaatIni->toDateString())
                ->where('periode_akhir', $periodeAkhirSaatIni->toDateString())
                ->first();

            if (!$existingOpname || !$existingOpname->approved) {
                Opname::updateOrCreate(
                    [
                        'barang_kode' => $barang->kode,
                        'periode_awal' => $periodeAwalSaatIni->toDateString(),
                        'periode_akhir' => $periodeAkhirSaatIni->toDateString(),
                    ],
                    [
                        'stock_awal' => $stockAwal,
                        'total_masuk' => $totalMasuk,
                        'total_keluar' => $totalKeluar,
                        'stock_total' => $stockTotal,
                        // 'total_lapangan' dan 'selisih' di-reset jika record di-update dan belum approved
                        // atau biarkan default jika baru dibuat (default(0) dari migrasi)
                        'total_lapangan' => $existingOpname && !$existingOpname->approved ? $existingOpname->total_lapangan : 0,
                        'selisih' => $existingOpname && !$existingOpname->approved ? ($stockTotal - $existingOpname->total_lapangan) : ($stockTotal - 0),
                        'keterangan' => $existingOpname && !$existingOpname->approved ? $existingOpname->keterangan : null,
                    ]
                );
                $this->info("Processed opname for Barang Kode: {$barang->kode} - {$barang->nama} for period {$periodeAwalSaatIni->toDateString()} to {$periodeAkhirSaatIni->toDateString()}");
            } else {
                $this->info("Skipped opname for Barang Kode: {$barang->kode} - already approved for period {$periodeAwalSaatIni->toDateString()} to {$periodeAkhirSaatIni->toDateString()}.");
            }
        }

        $this->info('Opname report generation complete.');
        Log::info('Scheduled opname report generated successfully for period: '.$periodeAwalSaatIni->toDateString().' to '.$periodeAkhirSaatIni->toDateString());
        return 0;
    }
}