<?php

use App\Http\Controllers\Panitia\PendaftarController;
use App\Http\Controllers\Panitia\DashboardController;
use App\Http\Controllers\Panitia\VerifikasiBerkasController;
use App\Http\Controllers\Panitia\WawancaraController;
use App\Http\Controllers\Panitia\SeleksiController;
use App\Http\Controllers\Panitia\DaftarUlangController;
use App\Http\Controllers\Panitia\TesSpmbController;
use App\Http\Controllers\Panitia\PembayaranController;
use App\Http\Controllers\Panitia\LayananPiketController;
use App\Http\Controllers\Panitia\AssistedRegistrationController;
use App\Http\Controllers\Shared\HasilTesController;
use App\Http\Controllers\Shared\MinatPromosiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'panitia'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/hasil-tes', [HasilTesController::class, 'index'])->name('hasil-tes.index');
    Route::get('/hasil-tes/{pendaftar}', [HasilTesController::class, 'show'])->name('hasil-tes.show');

    // Data Pendaftar
    Route::get('/pendaftar', [PendaftarController::class, 'index'])->name('pendaftar.index');
    Route::get('/pendaftaran-dibantu', [AssistedRegistrationController::class, 'create'])->name('pendaftaran_bantuan.create');
    Route::get('/pendaftaran-dibantu/cari-sekolah', [AssistedRegistrationController::class, 'searchSchool'])->name('pendaftaran_bantuan.sekolah.search');
    Route::post('/pendaftaran-dibantu', [AssistedRegistrationController::class, 'store'])->name('pendaftaran_bantuan.store');
    Route::get('/pendaftaran-dibantu/{pendaftar}/edit', [AssistedRegistrationController::class, 'edit'])->name('pendaftaran_bantuan.edit');
    Route::put('/pendaftaran-dibantu/{pendaftar}', [AssistedRegistrationController::class, 'update'])->name('pendaftaran_bantuan.update');
    Route::get('/pendaftar/export-excel', [PendaftarController::class, 'export'])->name('pendaftar.export');
    Route::get('/pendaftar/{pendaftar}', [PendaftarController::class, 'show'])->name('pendaftar.show');
    Route::get('/pendaftar/{pendaftar}/cetak', [PendaftarController::class, 'cetak'])->name('pendaftar.cetak');
    Route::get('/pendaftar/{pendaftar}/pdf', [PendaftarController::class, 'pdf'])->name('pendaftar.pdf');
    Route::put('/pendaftar/{pendaftar}/verifikasi', [PendaftarController::class, 'verify'])->name('pendaftar.verify');

    // Meja guru panitia piket: penerimaan calon siswa dan pembayaran formulir
    Route::redirect('/layanan-piket', '/panitia/kunjungan')->name('layanan-piket.redirect');
    Route::get('/kunjungan/export-excel', [LayananPiketController::class, 'export'])->name('kunjungan.export');
    Route::get('/kunjungan', [LayananPiketController::class, 'index'])->name('kunjungan.index');
    Route::get('/kunjungan/cari-sekolah', [LayananPiketController::class, 'searchSchool'])->name('kunjungan.sekolah.search');
    Route::post('/kunjungan', [LayananPiketController::class, 'store'])->name('kunjungan.store');
    Route::put('/kunjungan/{kunjungan}', [LayananPiketController::class, 'update'])->name('kunjungan.update');
    Route::delete('/kunjungan/{kunjungan}', [LayananPiketController::class, 'destroy'])->name('kunjungan.destroy');
    Route::get('/minat-promosi', [MinatPromosiController::class, 'index'])->name('promosi-minat.index');
    Route::get('/minat-promosi/export-excel', [MinatPromosiController::class, 'export'])->name('promosi-minat.export');
    Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');
    Route::patch('/pembayaran/transaksi/{transaksi}', [PembayaranController::class, 'verify'])->name('pembayaran.verify');
    Route::get('/pembayaran/transaksi/{transaksi}/bukti', [PembayaranController::class, 'viewProof'])->name('pembayaran.proof');

    // Verifikasi Berkas
    Route::get('/verifikasi', [VerifikasiBerkasController::class, 'index'])->name('verifikasi.index');
    Route::get('/verifikasi/{pendaftar}', [VerifikasiBerkasController::class, 'show'])->name('verifikasi.show');
    Route::get('/dokumen/{dokumen}/lihat', [VerifikasiBerkasController::class, 'viewDocument'])->name('dokumen.show');
    Route::put('/verifikasi/dokumen/{dokumen}', [VerifikasiBerkasController::class, 'verify'])->name('verifikasi.verify');

    // Wawancara (placeholder)
    Route::get('/wawancara', [WawancaraController::class, 'index'])->name('wawancara.index');

    // Tes SPMB
    Route::redirect('/tes-spmb', '/panitia/tes-spmb/btq')->name('tes.index');
    Route::get('/tes-spmb/kehadiran', [\App\Http\Controllers\Shared\DaftarHadirTesController::class, 'index'])->name('tes.attendance');
    Route::post('/tes-spmb/cbt/open-bulk', [TesSpmbController::class, 'openCbtAccessBulk'])->name('tes.cbt.open-bulk');
    Route::get('/tes-spmb/btq', [TesSpmbController::class, 'btq'])->name('tes.btq');
    Route::get('/tes-spmb/seragam', [TesSpmbController::class, 'uniform'])->name('tes.uniform');
    Route::get('/tes-spmb/kesehatan', [TesSpmbController::class, 'health'])->name('tes.health');
    Route::get('/tes-spmb/cbt', [TesSpmbController::class, 'cbt'])->name('tes.cbt');
    Route::get('/tes-spmb/wawancara', [TesSpmbController::class, 'interview'])->name('tes.interview');
    Route::post('/tes-spmb/{pendaftar}/hasil', [TesSpmbController::class, 'storeResult'])->name('tes.hasil.store');
    Route::post('/tes-spmb/{pendaftar}/seragam', [TesSpmbController::class, 'storeUniform'])->name('tes.seragam.store');
    Route::post('/tes-spmb/{pendaftar}/kesehatan', [TesSpmbController::class, 'storeHealth'])->name('tes.kesehatan.store');
    Route::post('/tes-spmb/{pendaftar}/cbt/open', [TesSpmbController::class, 'openCbtAccess'])->name('tes.cbt.open');
    Route::post('/tes-spmb/{pendaftar}/cbt/close', [TesSpmbController::class, 'closeCbtAccess'])->name('tes.cbt.close');
    Route::delete('/tes-spmb/{pendaftar}/hasil/{testKey}', [TesSpmbController::class, 'destroyResult'])->name('tes.hasil.destroy');

    // Seleksi Akhir
    Route::get('/seleksi', [SeleksiController::class, 'index'])->name('seleksi.index');
    Route::put('/seleksi/{pendaftar}', [SeleksiController::class, 'decide'])->name('seleksi.decide');

    // Daftar Ulang
    Route::get('/daftar-ulang', [DaftarUlangController::class, 'index'])->name('daftar_ulang.index');
    Route::put('/daftar-ulang/{pendaftar}', [DaftarUlangController::class, 'process'])->name('daftar_ulang.process');
});

