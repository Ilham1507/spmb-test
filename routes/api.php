<?php

use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/midtrans', \App\Http\Controllers\MidtransWebhookController::class)
    ->middleware('throttle:120,1')->name('webhooks.midtrans');

Route::get('/webhooks/whatsapp', [WhatsappWebhookController::class, 'verify'])
    ->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'receive'])
    ->name('webhooks.whatsapp.receive');
