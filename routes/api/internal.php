<?php

use App\Http\Controllers\Internal\AuthorizationInternalController;
use App\Http\Controllers\Internal\MasterDataInternalController;
use App\Http\Controllers\Internal\UserInternalController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal')->middleware('internal.service')->group(function (): void {
    Route::get('/users/{user}', [UserInternalController::class, 'show']);
    Route::middleware('internal.user')->group(function (): void {
        Route::prefix('master')->group(function (): void {
            Route::get('/mapping-values', [MasterDataInternalController::class, 'mappingValues']);
            Route::get('/mapping-values/table', [MasterDataInternalController::class, 'mappingValuesTable']);
            Route::get('/mapping-values/key/{key}', [MasterDataInternalController::class, 'mappingValuesByKey']);
            Route::get('/mapping-values/institutions', [MasterDataInternalController::class, 'institutionMappings']);
            Route::get('/mapping-values/lampiran', [MasterDataInternalController::class, 'lampiranMappings']);
            Route::get('/provinces', [MasterDataInternalController::class, 'provinces']);
            Route::get('/regencies', [MasterDataInternalController::class, 'regencies']);
            Route::get('/districts', [MasterDataInternalController::class, 'districts']);
            Route::get('/villages', [MasterDataInternalController::class, 'villages']);
        });
        Route::get('/users/{userId}/context', [AuthorizationInternalController::class, 'context']);
        Route::post('/permissions/check', [AuthorizationInternalController::class, 'checkPermission']);
        Route::post('/roles/check', [AuthorizationInternalController::class, 'checkRole']);
    });
});
