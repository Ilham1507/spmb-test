<?php

use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;


Route::get('/webhooks/whatsapp', [WhatsappWebhookController::class, 'verify'])
    ->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'receive'])
    ->name('webhooks.whatsapp.receive');
