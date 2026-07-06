<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanRekapitulasiController;
use App\Http\Controllers\LaporanUmurPiutangController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatPembayaranController;
use App\Http\Controllers\TagihanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('pelanggan', PelangganController::class);
    Route::resource('tagihan', TagihanController::class);

    Route::post('/tagihan/{tagihan}/bayar', [TagihanController::class, 'bayar'])
        ->name('tagihan.bayar');

    Route::get('/tagihan/{tagihan}/pdf', [TagihanController::class, 'exportPdf'])
        ->name('tagihan.pdf');

    Route::get('/laporan/umur-piutang', LaporanUmurPiutangController::class)
        ->name('laporan.umur-piutang');
    Route::get('/laporan/piutang/export-excel', [LaporanUmurPiutangController::class, 'exportExcel'])
        ->name('laporan.piutang.export');
    Route::get('/laporan/riwayat-pembayaran', RiwayatPembayaranController::class)
        ->name('riwayat-pembayaran');
    Route::get('/laporan/rekapitulasi', LaporanRekapitulasiController::class)
        ->name('laporan.rekapitulasi');
});

require __DIR__.'/auth.php';
