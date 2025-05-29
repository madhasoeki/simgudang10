<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tempat;
use App\Models\StatusTempat;
use App\Models\BarangKeluar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateStatusTempatReport extends Command
{
    protected $signature = 'statustempat:generate';
    protected $description = 'Generate or update status_tempat records for the current opname period';

    public function handle()
    {
        $this->info('Starting to generate status tempat report...');

        $today = Carbon::today();

        if ($today->day >= 26) {
            $periodeAwalSaatIni = $today->copy()->day(26);
            $periodeAkhirSaatIni = $today->copy()->addMonth()->day(25);
        } else {
            $periodeAwalSaatIni = $today->copy()->subMonth()->day(26);
            $periodeAkhirSaatIni = $today->copy()->day(25);
        }
        
        $this->info("Processing for opname period: {$periodeAwalSaatIni->toDateString()} to {$periodeAkhirSaatIni->toDateString()}");

        $tempats = Tempat::all();

        foreach ($tempats as $tempat) {
            // Hitung total jumlah harga barang keluar untuk tempat dan periode ini
            // Asumsi di tabel barang_keluar ada kolom 'qty' dan 'harga', dan 'tanggal' untuk tanggal keluar
            $totalBarangKeluar = BarangKeluar::where('tempat_id', $tempat->id)
                ->whereBetween('tanggal', [$periodeAwalSaatIni, $periodeAkhirSaatIni])
                ->sum(DB::raw('qty * harga')); // Menjumlahkan hasil perkalian qty dan harga

            $statusTempat = StatusTempat::firstOrNew([
                'tempat_id' => $tempat->id,
                'periode_awal' => $periodeAwalSaatIni->toDateString(),
                'periode_akhir' => $periodeAkhirSaatIni->toDateString(),
            ]);

            // Hanya update total dan status ke 'loading' jika statusnya belum 'done'
            // Jika sudah 'done', biarkan, kecuali ada logika lain yang diinginkan
            if ($statusTempat->status !== 'done') {
                $statusTempat->total = $totalBarangKeluar ?? 0;
                $statusTempat->status = 'loading'; // Set atau reset ke loading jika di-refresh
                $statusTempat->save();
                $this->info("Processed status for Tempat ID: {$tempat->id} - {$tempat->nama}. Total: {$statusTempat->total}");
            } else {
                 // Jika sudah done, mungkin kita hanya update totalnya saja? Atau tidak sama sekali?
                 // Untuk sekarang, jika sudah done, kita update totalnya saja, status tetap done.
                 // Jika kamu ingin status kembali ke loading saat refresh, hapus kondisi if ini.
                if ($statusTempat->exists) { // Hanya update total jika record sudah ada
                    $statusTempat->total = $totalBarangKeluar ?? 0;
                    $statusTempat->save();
                    $this->info("Updated total for Tempat ID: {$tempat->id} - {$tempat->nama} (status remains 'done'). Total: {$statusTempat->total}");
                } else {
                    // Jika record baru dan seharusnya langsung 'done' berdasarkan logika lain (tidak ada di sini)
                    // Untuk skenario ini, record baru akan selalu 'loading' karena default migrasi.
                    // Baris di bawah ini tidak akan terpicu jika firstOrNew menemukan record yg statusnya 'done'
                    $statusTempat->total = $totalBarangKeluar ?? 0;
                    $statusTempat->status = 'loading'; 
                    $statusTempat->save();
                    $this->info("Created status for Tempat ID: {$tempat->id} - {$tempat->nama}. Total: {$statusTempat->total}");
                }
            }
        }

        $this->info('Status tempat report generation complete.');
        Log::info('Scheduled status tempat report generated successfully for period: '.$periodeAwalSaatIni->toDateString().' to '.$periodeAkhirSaatIni->toDateString());
        return 0;
    }
}