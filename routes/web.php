<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/', [PaymentController::class, 'index']);
Route::post('/process-payment', [PaymentController::class, 'processPayment']);
