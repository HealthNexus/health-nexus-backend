<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/diseases', [RecordController::class, 'diseasesData']);

// Paystack callback route (accessible without /api prefix for web redirects)
Route::get('/payments/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');
