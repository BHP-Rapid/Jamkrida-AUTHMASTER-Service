<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')
    ->group(function (): void {
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/admin/login', [AuthController::class, 'loginAdmin']);
        Route::post('/auth/admin/verify-otp', [AuthController::class, 'verifyAdminOtp']);
        Route::post('/auth/mitra/login', [AuthController::class, 'loginMitra']);
        Route::post('/auth/mitra/verify-otp', [AuthController::class, 'verifyMitraOtp']);
    });
