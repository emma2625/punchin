<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SubscriptionPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/otp/resend', [OtpController::class, 'resend']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::get('/subscriptions', [SubscriptionController::class, 'index']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [MeController::class, 'profile']);
    Route::post('/add-staff', [StaffController::class, 'addStaff']);

    Route::prefix('subscriptions')->controller(SubscriptionPaymentController::class)->group(function(){
        Route::post('/initialize', 'initialize');
        Route::post('/verify', 'verify')->name('subscriptions.verify');
    });
});

Route::fallback(static fn () => response()->json(status: 404));
