<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}/events', [PaymentController::class, 'events']);
    Route::post('/payments/{id}/refund', [AdminController::class, 'refund']);
});
