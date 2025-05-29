<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\OpnameController;
use App\Http\Controllers\TempatController;
use App\Http\Controllers\DataMissController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BarangMasukController;
use App\Http\Controllers\BarangKeluarController;
use App\Http\Controllers\RekapLaporanController;
use App\Http\Controllers\LaporanPerTempatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route untuk menampilkan form login dan proses login
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);

// Route untuk proses logout
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Semua route di bawah ini wajib login
Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    // Kelola barang
    Route::resource('barang', BarangController::class);
    
    // Kelola tempat
    Route::resource('tempat', TempatController::class);

    // Kelola barang masuk
    Route::resource('barang-masuk', BarangMasukController::class)->except([
        'show'
    ]);
    // Rute tambahan untuk AJAX DataTables
    Route::get('barang-masuk/data', [BarangMasukController::class, 'data'])->name('barang-masuk.data');

    // Kelola barang keluar
    Route::resource('barang-keluar', BarangKeluarController::class)->except([
        'show' // Kita mungkin tidak butuh halaman show untuk transaksi
    ]);
    // Rute tambahan untuk AJAX DataTables Barang Keluar
    Route::get('barang-keluar/data', [BarangKeluarController::class, 'data'])->name('barang-keluar.data');
    // Rute tambahan untuk mendapatkan harga barang yang tersedia (untuk dropdown dinamis)
    Route::get('barang-keluar/get-harga-stok/{barang_kode}', [BarangKeluarController::class, 'getHargaStokTersedia'])->name('barang-keluar.getHargaStok');


    // Route untuk Opname
    Route::prefix('opname')->name('opname.')->group(function () {
        // Halaman utama untuk menampilkan laporan opname
        Route::get('/', [OpnameController::class, 'index'])->name('index');

        // Route untuk mengambil data via AJAX untuk DataTables
        Route::get('/data', [OpnameController::class, 'data'])->name('data');

        // Menjalankan command refresh data secara manual
        Route::post('/refresh', [OpnameController::class, 'refresh'])->name('refresh');

        // Menyimpan data lapangan dan keterangan yang diinput user
        Route::put('/update/{opname}', [OpnameController::class, 'update'])->name('update');

        // Proses approval oleh super admin
        Route::post('/approve/{opname}', [OpnameController::class, 'approve'])->name('approve')->middleware('can:approve opname');
        
        // Proses pembatalan approval oleh super admin
        Route::post('/cancel-approval/{opname}', [OpnameController::class, 'cancelApproval'])->name('cancel.approval')->middleware('can:approve opname');
    });

    // Lihat data miss
    Route::get('data-miss', [DataMissController::class, 'index'])->name('data-miss');
    Route::get('data-miss/data', [DataMissController::class, 'data'])->name('data-miss.data');

    Route::prefix('laporan')->name('laporan.')->group(function () {
        
        // Route untuk Laporan per Tempat
        Route::get('/per-tempat', [LaporanPerTempatController::class, 'index'])->name('per-tempat.index');
        Route::get('/per-tempat/data', [LaporanPerTempatController::class, 'data'])->name('per-tempat.data');

        // Route untuk Rekap Laporan
        Route::get('/rekap-status-tempat', [RekapLaporanController::class, 'index'])->name('rekap-status-tempat.index');
        Route::get('/rekap-status-tempat/data', [RekapLaporanController::class, 'data'])->name('rekap-status-tempat.data');
        Route::post('/rekap-status-tempat/refresh', [RekapLaporanController::class, 'refresh'])->name('rekap-status-tempat.refresh');
        Route::post('/rekap-status-tempat/toggle-status/{statusTempat}', [RekapLaporanController::class, 'toggleStatus'])->name('rekap-status-tempat.toggle-status');
    });

    
    // Route khusus super admin
    // Route::middleware(['role:super admin'])->group(function () {
    //     Route::get('/kelola-user', [KelolaUserController::class, 'index'])->name('kelola-user');
    //     Route::get('/history', [HistoryController::class, 'index'])->name('history');
    // });
});