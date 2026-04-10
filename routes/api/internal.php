<?php

use App\Http\Controllers\Internal\AuthorizationInternalController;
use App\Http\Controllers\Internal\UserInternalController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal')->middleware('internal.service')->group(function (): void {
    Route::get('/users/{user}', [UserInternalController::class, 'show']);
    Route::middleware('internal.user')->group(function (): void {
        Route::get('/users/{userId}/context', [AuthorizationInternalController::class, 'context']);
        Route::post('/permissions/check', [AuthorizationInternalController::class, 'checkPermission']);
        Route::post('/roles/check', [AuthorizationInternalController::class, 'checkRole']);
    });
});
