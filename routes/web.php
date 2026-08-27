<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LaporanRekapitulasiController;
use App\Http\Controllers\LaporanUmurPiutangController;
use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatPembayaranController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('pelanggan/suggest', [PelangganController::class, 'suggest'])
        ->middleware('throttle:30,1')
        ->name('pelanggan.suggest');
    Route::resource('pelanggan', PelangganController::class);
    Route::get('pelanggan/{pelanggan}/info', [PelangganController::class, 'info'])
        ->middleware('throttle:30,1')
        ->name('pelanggan.info');

    Route::get('tagihan/suggest', [TagihanController::class, 'suggest'])
        ->middleware('throttle:30,1')
        ->name('tagihan.suggest');
    Route::resource('tagihan', TagihanController::class);

    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::post('preview', [ImportController::class, 'preview'])->name('preview');
        Route::post('store', [ImportController::class, 'store'])->name('store');
    });

    Route::resource('users', UserController::class)
        ->except(['show']);

    Route::post('/tagihan/{tagihan}/bayar', [TagihanController::class, 'bayar'])
        ->name('tagihan.bayar');

    Route::patch('tagihan/{tagihan}/status-penagihan',
        [TagihanController::class, 'updateStatus'])
        ->name('tagihan.update-status');

    Route::patch('tagihan/{tagihan}/assign-sales',
        [TagihanController::class, 'assignSales'])
        ->name('tagihan.assign-sales');

    Route::get('tagihan/{tagihan}/sales',
        [TagihanController::class, 'showSales'])
        ->name('tagihan.show-sales');

    Route::get('/tagihan/{tagihan}/pdf', [TagihanController::class, 'exportPdf'])
        ->name('tagihan.pdf');

    Route::get('/laporan/umur-piutang', LaporanUmurPiutangController::class)
        ->name('laporan.umur-piutang');
    Route::get('/laporan/piutang/export-excel', [LaporanUmurPiutangController::class, 'exportExcel'])
        ->name('laporan.piutang.export');
    Route::get('/laporan/riwayat-pembayaran', RiwayatPembayaranController::class)
        ->name('riwayat-pembayaran');
    Route::get('/laporan/rekapitulasi/export', [LaporanRekapitulasiController::class, 'exportExcel'])
        ->name('laporan.rekapitulasi.export');
    Route::get('/laporan/rekapitulasi', LaporanRekapitulasiController::class)
        ->name('laporan.rekapitulasi');

    Route::prefix('approval')->name('approval.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::post('{tagihan}/setujui', [ApprovalController::class, 'setujui'])->name('setujui');
        Route::post('{tagihan}/tolak', [ApprovalController::class, 'tolak'])->name('tolak');
    });

    Route::get('/log-aktivitas', [LogAktivitasController::class, 'index'])
        ->name('log-aktivitas');
});

require __DIR__.'/auth.php';
