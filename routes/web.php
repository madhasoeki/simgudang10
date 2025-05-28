<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TempatController;

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
    Route::prefix('barang')->name('barang.')->group(function () {
        Route::get('/', [BarangController::class, 'index'])->name('index');
        Route::get('/create', [BarangController::class, 'create'])->name('create');
        Route::post('/', [BarangController::class, 'store'])->name('store');
        Route::get('/{barang}/edit', [BarangController::class, 'edit'])->name('edit');
        Route::put('/{barang}', [BarangController::class, 'update'])->name('update');
        Route::delete('/{barang}', [BarangController::class, 'destroy'])->name('destroy');
    });

    // Kelola tempat
    Route::prefix('tempat')->name('tempat.')->group(function () {
        Route::get('/', [TempatController::class, 'index'])->name('index');
        Route::get('/create', [TempatController::class, 'create'])->name('create');
        Route::post('/', [TempatController::class, 'store'])->name('store');
        Route::get('/{barang}/edit', [TempatController::class, 'edit'])->name('edit');
        Route::put('/{barang}', [TempatController::class, 'update'])->name('update');
        Route::delete('/{barang}', [TempatController::class, 'destroy'])->name('destroy');
    });

    // Route khusus super admin
    // Route::middleware(['role:super admin'])->group(function () {
    //     Route::get('/kelola-user', [App\Http\Controllers\KelolaUserController::class, 'index'])->name('kelola-user');
    //     Route::get('/history', [App\Http\Controllers\HistoryController::class, 'index'])->name('history');
    // });
});