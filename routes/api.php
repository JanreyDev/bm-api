<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LocationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::get('/locations', [LocationController::class, 'index']);
Route::get('/locations/address-directory', [LocationController::class, 'index']);
Route::get('/locations/directory', [LocationController::class, 'index']);

Route::middleware('auth:api')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/activation/complete', [AuthController::class, 'completeActivation']);
});
