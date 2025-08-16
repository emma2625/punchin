<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/otp/resend', [OtpController::class, 'resend']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::get('/subscriptions', [SubscriptionController::class, 'index']);

Route::middleware('api')->group(function (){
    Route::get('/me', [MeController::class, 'profile']);
});

Route::fallback(static fn () => response()->json(status: 404));
