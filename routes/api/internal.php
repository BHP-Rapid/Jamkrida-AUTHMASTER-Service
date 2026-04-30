<?php

use App\Http\Controllers\Internal\AuthorizationInternalController;
use App\Http\Controllers\Internal\MasterDataInternalController;
use App\Http\Controllers\Internal\MitraInternalController;
use App\Http\Controllers\Internal\NotifAdminInternalController;
use App\Http\Controllers\Internal\NotifInternalController;
use App\Http\Controllers\Internal\RoleInternalController;
use App\Http\Controllers\Internal\SettingsInternalController;
use App\Http\Controllers\Internal\UserAdminInternalController;
use App\Http\Controllers\Internal\UserInternalController;
use App\Http\Controllers\Internal\UserMitraInternalController;
use Illuminate\Support\Facades\Route;

Route::prefix('int')->middleware('internal.service')->group(function (): void {
    Route::get('/users/{user}', [UserInternalController::class, 'show']);
    Route::post('/admin-users/register', [UserAdminInternalController::class, 'storeRegister']);
    Route::post('/mitra-users/register', [UserMitraInternalController::class, 'storeRegister']);
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
        Route::prefix('roles')->group(function (): void {
            Route::get('/me', [RoleInternalController::class, 'index']);
            Route::get('/', [RoleInternalController::class, 'getAllRoles']);
            Route::get('/access', [RoleInternalController::class, 'getAccessByRole']);
            Route::put('/access', [RoleInternalController::class, 'updateRole']);
            Route::get('/type/{roleType}', [RoleInternalController::class, 'getRoleByType']);
        });
        Route::prefix('settings')->group(function (): void {
            Route::get('/', [SettingsInternalController::class, 'index']);
            Route::get('/detail', [SettingsInternalController::class, 'show']);
            Route::get('/mitra/{mitraId}', [SettingsInternalController::class, 'getSettingsByMitraId']);
            Route::get('/menu', [SettingsInternalController::class, 'showSettingsMenu']);
            Route::get('/lampiran-menu', [SettingsInternalController::class, 'getLampiranSettingsMenu']);
            Route::post('/', [SettingsInternalController::class, 'store']);
            Route::put('/', [SettingsInternalController::class, 'update']);
            Route::patch('/mandatory', [SettingsInternalController::class, 'updateMandatory']);
        });
        Route::prefix('mitra')->group(function (): void {
            Route::get('/creatio', [MitraInternalController::class, 'getMitraFromCreatio']);
            Route::get('/data', [MitraInternalController::class, 'getDataMitra']);
            Route::get('/detail', [MitraInternalController::class, 'getDataByMitraId']);
            Route::post('/', [MitraInternalController::class, 'store']);
            Route::put('/', [MitraInternalController::class, 'updateByMitraId']);
        });
        Route::prefix('notif')->group(function (): void {
            Route::get('/', [NotifInternalController::class, 'getNotif']);
            Route::get('/count', [NotifInternalController::class, 'countNotif']);
            Route::patch('/', [NotifInternalController::class, 'update']);
            Route::patch('/all', [NotifInternalController::class, 'updateAllNotif']);
        });
        Route::prefix('notif-admin')->group(function (): void {
            Route::get('/', [NotifAdminInternalController::class, 'index']);
            Route::get('/mitra-recipient', [NotifAdminInternalController::class, 'getMitraRecipient']);
            Route::post('/', [NotifAdminInternalController::class, 'createNotifAdmin']);
            Route::get('/{id}', [NotifAdminInternalController::class, 'getById']);
        });
        Route::prefix('admin-users')->group(function (): void {
            Route::get('/', [UserAdminInternalController::class, 'index']);
            Route::get('/list', [UserAdminInternalController::class, 'getUsersByRole']);
            Route::get('/verification', [UserAdminInternalController::class, 'getDataVerification']);
            Route::get('/role-list', [UserAdminInternalController::class, 'getRoleList']);
            Route::get('/mitra-list', [UserAdminInternalController::class, 'getAdminMitraList']);
            Route::get('/{userId}', [UserAdminInternalController::class, 'getDataById']);
            Route::put('/{userId}', [UserAdminInternalController::class, 'updateAdminByUserId']);
            Route::patch('/{userId}/status-approval', [UserAdminInternalController::class, 'updateStatusApproval']);
            Route::patch('/{userId}/status', [UserAdminInternalController::class, 'updateStatus']);
            Route::delete('/{userId}', [UserAdminInternalController::class, 'deleteUser']);
            Route::post('/change-password', [UserAdminInternalController::class, 'changePassword']);
        });
        Route::prefix('mitra-users')->group(function (): void {
            Route::get('/', [UserMitraInternalController::class, 'index']);
            Route::get('/list', [UserMitraInternalController::class, 'getUsersByRole']);
            Route::get('/verification', [UserMitraInternalController::class, 'getDataVerification']);
            Route::post('/', [UserMitraInternalController::class, 'store']);
            Route::post('/upload-excel', [UserMitraInternalController::class, 'uploadExcel']);
            Route::post('/change-password', [UserMitraInternalController::class, 'changePassword']);
            Route::get('/{userId}', [UserMitraInternalController::class, 'getDataById']);
            Route::put('/{userId}', [UserMitraInternalController::class, 'updateByUserId']);
            Route::patch('/{userId}/status', [UserMitraInternalController::class, 'updateStatus']);
            Route::patch('/{userId}/status-approval', [UserMitraInternalController::class, 'updateStatusApproval']);
            Route::delete('/{userId}', [UserMitraInternalController::class, 'deleteUser']);
        });
        Route::get('/users/{userId}/context', [AuthorizationInternalController::class, 'context']);
        Route::post('/permissions/check', [AuthorizationInternalController::class, 'checkPermission']);
        Route::post('/roles/check', [AuthorizationInternalController::class, 'checkRole']);
    });
});
