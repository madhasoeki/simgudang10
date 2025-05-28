<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\TempatController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BarangMasukController;

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
    
    // Route khusus super admin
    // Route::middleware(['role:super admin'])->group(function () {
    //     Route::get('/kelola-user', [App\Http\Controllers\KelolaUserController::class, 'index'])->name('kelola-user');
    //     Route::get('/history', [App\Http\Controllers\HistoryController::class, 'index'])->name('history');
    // });
});