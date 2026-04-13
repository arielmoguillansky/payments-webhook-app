<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::post('/webhooks/payment', [WebhookController::class, 'handle']);
