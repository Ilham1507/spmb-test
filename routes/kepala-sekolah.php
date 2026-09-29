<?php

use App\Http\Controllers\KepalaSekolah\DashboardController;
use App\Http\Controllers\Admin\BeritaLandingController;
use App\Http\Controllers\Shared\ExecutiveReportController;
use App\Http\Controllers\Panitia\PendaftarController;
use App\Http\Controllers\Panitia\AssistedRegistrationController;
use App\Http\Controllers\Panitia\LayananPiketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'kepala_sekolah'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
    Route::get('/kunjungan/export-excel', [LayananPiketController::class, 'export'])->name('kunjungan.export');
    Route::get('/kunjungan', [LayananPiketController::class, 'index'])->name('kunjungan.index');
    Route::get('/kunjungan/cari-sekolah', [LayananPiketController::class, 'searchSchool'])->name('kunjungan.sekolah.search');
    Route::get('/berita-artikel', [BeritaLandingController::class, 'index'])->name('berita-landing.index');
    Route::get('/berita-artikel/buat', [BeritaLandingController::class, 'create'])->name('berita-landing.create');
    Route::post('/berita-artikel', [BeritaLandingController::class, 'store'])->name('berita-landing.store');
    Route::get('/berita-artikel/{berita}/ubah', [BeritaLandingController::class, 'edit'])->name('berita-landing.edit');
    Route::put('/berita-artikel/{berita}', [BeritaLandingController::class, 'update'])->name('berita-landing.update');
    Route::delete('/berita-artikel/{berita}', [BeritaLandingController::class, 'destroy'])->name('berita-landing.destroy');
    Route::get('/laporan-eksekutif', [ExecutiveReportController::class, 'index'])->name('laporan-eksekutif.index');
    Route::get('/laporan-eksekutif/pdf', [ExecutiveReportController::class, 'pdf'])->name('laporan-eksekutif.pdf');
    Route::get('/laporan-eksekutif/excel', [ExecutiveReportController::class, 'exportExcel'])->name('laporan-eksekutif.excel');
});
