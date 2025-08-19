<?php

use App\Http\Controllers\Api\Auth\LoginController as APILogin;
use App\Http\Controllers\Api\Auth\LogoutController as CustomLogout;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SubscriptionPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [APILogin::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [CustomLogout::class, 'logout']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/otp/resend', [OtpController::class, 'resend']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::get('/subscriptions', [SubscriptionController::class, 'index']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [MeController::class, 'profile']);
    Route::get('/dashboard-stats', [DashboardController::class, 'getStats']);

    Route::patch('/profile', [ProfileController::class, 'updateUserProfile']);
    Route::patch('/company-profile', [ProfileController::class, 'updateCompanyProfile']);

    Route::get('/staff', [StaffController::class, 'getStaff']);
    Route::post('/staff', [StaffController::class, 'addStaff']);
    Route::put('/staff/{staff}/branch', [StaffController::class, 'updateStaffBranch']);
    Route::delete('/staff/{staff}', [StaffController::class, 'removeStaff']);

    Route::get('/branches', [BranchController::class, 'index']);
    Route::post('/branches', [BranchController::class, 'store']);
    Route::put('/branches/{branch}', [BranchController::class, 'update']);
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

    Route::prefix('subscriptions')->controller(SubscriptionPaymentController::class)->group(function () {
        Route::post('/initialize', 'initialize');
        Route::post('/verify', 'verify')->name('subscriptions.verify');
    });
});

Route::fallback(static fn() => response()->json(status: 404));
