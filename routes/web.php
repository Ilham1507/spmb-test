<?php

use App\Http\Controllers\{AuthController, BankAccountController, CandidateFormController, PaymentController, SiswaController};
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::resource('siswa', SiswaController::class)->except(['create', 'show', 'edit']);

// Halaman autentikasi ringan untuk tampilan portal.
Route::view('/login', 'auth.login')->middleware('guest')->name('login');
Route::view('/register', 'auth.register')->middleware('guest')->name('register');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.submit');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest')->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/aktivasi/{siswa}', [AuthController::class, 'showActivation'])->name('activation.show');
Route::post('/aktivasi/{siswa}', [AuthController::class, 'activate'])->name('activation.complete');
Route::get('/invoice-publik/{payment}.pdf', [PaymentController::class, 'publicInvoicePdf'])->name('payments.invoice.pdf.public');
Route::get('/formulir/{siswa}/lanjut', [CandidateFormController::class, 'show'])->name('candidate-form.show');
Route::post('/formulir/{siswa}/lanjut', [CandidateFormController::class, 'store'])->name('candidate-form.store');

Route::middleware('auth')->group(function () {
    Route::get('/pembayaran', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/pembayaran', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/pembayaran/{payment}/setujui', [PaymentController::class, 'approve'])->middleware('payment.role:panitia,admin')->name('payments.approve');
    Route::post('/pembayaran/{payment}/terima', [PaymentController::class, 'receive'])->middleware('payment.role:bendahara,admin')->name('payments.receive');
    Route::get('/pembayaran/{payment}/invoice', [PaymentController::class, 'invoice'])->name('payments.invoice');
    Route::get('/pembayaran/{payment}/invoice.pdf', [PaymentController::class, 'invoicePdf'])->name('payments.invoice.pdf');
    Route::get('/keuangan/rekening', [BankAccountController::class, 'index'])->middleware('payment.role:bendahara,admin')->name('bank-accounts.index');
    Route::post('/keuangan/rekening', [BankAccountController::class, 'store'])->middleware('payment.role:bendahara,admin')->name('bank-accounts.store');
    Route::post('/keuangan/rekening/{bankAccount}/toggle', [BankAccountController::class, 'toggle'])->middleware('payment.role:bendahara,admin')->name('bank-accounts.toggle');
});
