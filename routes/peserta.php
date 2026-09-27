<?php

use App\Http\Controllers\Peserta\DashboardController;
use App\Http\Controllers\Peserta\BiodataController;
use App\Http\Controllers\Peserta\AlamatController;
use App\Http\Controllers\Peserta\AyahController;
use App\Http\Controllers\Peserta\IbuController;
use App\Http\Controllers\Peserta\WaliController;
use App\Http\Controllers\Peserta\SekolahAsalController;
use App\Http\Controllers\Peserta\JurusanController;
use App\Http\Controllers\Peserta\DokumenController;
use App\Http\Controllers\Peserta\ReviewController;
use App\Http\Controllers\Peserta\CbtController;
use App\Http\Controllers\Peserta\KunjunganMatchController;
use App\Http\Controllers\Peserta\CetakController;
use App\Http\Controllers\Peserta\KontakController;
use App\Http\Controllers\Shared\HasilTesController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('/verifikasi-email/{token}', [KontakController::class, 'verifyEmail'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->name('kontak.verify-email');

Route::middleware(['web', 'peserta'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/hasil-tes', [HasilTesController::class, 'index'])->name('hasil-tes.index');
    Route::get('/cetak', [CetakController::class, 'index'])->name('cetak');
    Route::get('/formulir', [CetakController::class, 'preview'])->name('formulir');
    Route::get('/pdf', [CetakController::class, 'pdf'])->name('pdf');
    Route::post('/kunjungan/{kunjungan}/kirim-otp', [KunjunganMatchController::class, 'sendOtp'])
        ->middleware('throttle:3,1')->name('kunjungan.otp.send');
    Route::post('/kunjungan/{kunjungan}/verifikasi', [KunjunganMatchController::class, 'verify'])
        ->middleware('throttle:8,1')->name('kunjungan.otp.verify');
    Route::post('/kunjungan/{kunjungan}/bukan-saya', [KunjunganMatchController::class, 'dismiss'])
        ->name('kunjungan.dismiss');
    Route::get('/cbt', [CbtController::class, 'index'])->name('cbt');
    Route::post('/cbt/lock', [CbtController::class, 'lock'])->name('cbt.lock');
    Route::post('/cbt/answers', [CbtController::class, 'saveAnswer'])->name('cbt.answers.save');
    Route::post('/cbt', [CbtController::class, 'submit'])->name('cbt.submit');
    Route::get('/review',   [ReviewController::class, 'index'])
        ->middleware([
            \App\Http\Middleware\EnsureRegistrationFeePaid::class,
            \App\Http\Middleware\EnsurePesertaStepOrder::class,
        ])
        ->name('review');

    Route::middleware([
        \App\Http\Middleware\EnsureRegistrationFeePaid::class,
        \App\Http\Middleware\CheckIfNotSubmitted::class,
        \App\Http\Middleware\EnsurePesertaStepOrder::class,
    ])->group(function () {
        Route::get('/biodata',  [BiodataController::class, 'index'])->name('biodata');
        Route::post('/biodata', [BiodataController::class, 'store']);

        Route::get('/alamat',   [AlamatController::class, 'index'])->name('alamat');
        Route::post('/alamat',  [AlamatController::class, 'store']);

        Route::get('/ayah',     [AyahController::class, 'index'])->name('ayah');
        Route::post('/ayah',    [AyahController::class, 'store']);

        Route::get('/ibu',      [IbuController::class, 'index'])->name('ibu');
        Route::post('/ibu',     [IbuController::class, 'store']);

        Route::get('/wali',     [WaliController::class, 'index'])->name('wali');
        Route::post('/wali',    [WaliController::class, 'store']);

        Route::get('/sekolah-asal/search', [SekolahAsalController::class, 'search'])->name('sekolah.search');
        Route::get('/sekolah-asal', [SekolahAsalController::class, 'index'])->name('sekolah');
        Route::post('/sekolah-asal', [SekolahAsalController::class, 'store']);

        Route::get('/jurusan',  [JurusanController::class, 'index'])->name('jurusan');
        Route::post('/jurusan', [JurusanController::class, 'store']);

        Route::get('/kontak', [KontakController::class, 'index'])->name('kontak');
        Route::post('/kontak', [KontakController::class, 'store']);

        Route::get('/dokumen',  [DokumenController::class, 'index'])->name('dokumen');
        Route::post('/dokumen', [DokumenController::class, 'store']);

        Route::post('/review',  [ReviewController::class, 'submit'])->name('submit');
    });

    // Keuangan / Pembayaran
    Route::get('/biaya-jurusan', [\App\Http\Controllers\Peserta\PembayaranController::class, 'majorFees'])->name('biaya-jurusan');
    Route::post('/biaya-jurusan/{jurusan}/daftar-ulang', [\App\Http\Controllers\Peserta\PembayaranController::class, 'startReRegistration'])->name('biaya-jurusan.daftar-ulang');
    Route::post('/pembayaran/{tagihan}/checkout', [\App\Http\Controllers\Peserta\PaymentCheckoutController::class, 'store'])
        ->middleware('throttle:6,1')->name('pembayaran.checkout');
    Route::post('/pembayaran-online/{checkout}/cek', [\App\Http\Controllers\Peserta\PaymentCheckoutController::class, 'refresh'])
        ->middleware('throttle:10,1')->name('pembayaran.refresh');
    Route::post('/pembayaran-online/{checkout}/batalkan', [\App\Http\Controllers\Peserta\PaymentCheckoutController::class, 'cancel'])
        ->middleware('throttle:3,1')->name('pembayaran.cancel');
    Route::get('/pembayaran/transaksi/{transaksi}/nota', [\App\Http\Controllers\Peserta\PembayaranController::class, 'receipt'])->name('pembayaran.receipt');
    Route::get('/pembayaran', [\App\Http\Controllers\Peserta\PembayaranController::class, 'index'])->name('pembayaran');
    Route::post('/pembayaran/{tagihan}', [\App\Http\Controllers\Peserta\PembayaranController::class, 'store'])->name('pembayaran.store');
});
