<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\PaymentController;

Route::post('/webhooks/payment', [WebhookController::class, 'handle']);

Route::get('/payments', [PaymentController::class, 'index']);
Route::get('/payments/{id}/events', [PaymentController::class, 'events']);
