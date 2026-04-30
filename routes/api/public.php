<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MitraController;
use App\Http\Controllers\Api\NotifAdminController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\NotifController;
use App\Http\Controllers\Api\UserAdminController;
use App\Http\Controllers\Api\MappingValueController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\UserMitraController;
use App\Http\Controllers\Api\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('pub')
    ->group(function (): void {
        Route::get('/bank-values', [PublicController::class, 'index']);
        Route::match(['get', 'post'], '/check-id', [PublicController::class, 'checkId']);
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/admin/login', [AuthController::class, 'loginAdmin']);
        Route::post('/auth/admin/verify-otp', [AuthController::class, 'verifyAdminOtp']);
        Route::post('/auth/mitra/login', [AuthController::class, 'loginMitra']);
        Route::post('/auth/mitra/verify-otp', [AuthController::class, 'verifyMitraOtp']);
        Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);
        Route::get('/auth/reset-password/validate', [AuthController::class, 'validateResetUrl']);
        Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/auth/reset-password/resend-email', [AuthController::class, 'resendResetPasswordEmail']);
        Route::get('/settings/general', [SettingsController::class, 'showGeneralSettings']);

        Route::prefix('master')->middleware('jwt.auth')->group(function (): void {
            Route::get('/mapping-values', [MappingValueController::class, 'index']);
            Route::get('/mapping-values/table', [MappingValueController::class, 'indexTableMapping']);
            Route::get('/mapping-values/key/{key}', [MappingValueController::class, 'getByKey']);
            Route::get('/mapping-values/institutions', [MappingValueController::class, 'getListDataInstitutionMappingValue']);
            Route::get('/mapping-values/lampiran', [MappingValueController::class, 'getLampiranMapping']);
            Route::get('/provinces', [MappingValueController::class, 'getProvince']);
            Route::get('/regencies', [MappingValueController::class, 'getRegency']);
            Route::get('/districts', [MappingValueController::class, 'getDistrict']);
            Route::get('/villages', [MappingValueController::class, 'getVillage']);
        });

        Route::prefix('roles')->middleware('jwt.auth')->group(function (): void {
            Route::get('/me', [UserRoleController::class, 'index']);
            Route::get('/', [UserRoleController::class, 'getAllRoles']);
            Route::get('/access', [UserRoleController::class, 'getAccessByRole']);
            Route::put('/access', [UserRoleController::class, 'updateRole']);
            Route::get('/type/{roleType}', [UserRoleController::class, 'getRoleByType']);
        });

        Route::prefix('settings')->middleware('jwt.auth')->group(function (): void {
            Route::get('/', [SettingsController::class, 'index']);
            Route::get('/detail', [SettingsController::class, 'show']);
            Route::get('/mitra/{mitraId}', [SettingsController::class, 'getSettingsByMitraId']);
            Route::get('/menu', [SettingsController::class, 'showSettingsMenu']);
            Route::get('/lampiran-menu', [SettingsController::class, 'getLampiranSettingsMenu']);
            Route::post('/', [SettingsController::class, 'store']);
            Route::put('/', [SettingsController::class, 'update']);
            Route::patch('/mandatory', [SettingsController::class, 'updateMandatory']);
        });

        Route::prefix('mitra')->middleware('jwt.auth')->group(function (): void {
            Route::get('/creatio', [MitraController::class, 'getMitraFromCreatio']);
            Route::get('/data', [MitraController::class, 'getDataMitra']);
            Route::get('/detail', [MitraController::class, 'getDataByMitraId']);
            Route::post('/', [MitraController::class, 'store']);
            Route::put('/', [MitraController::class, 'updateByMitraId']);
        });

        Route::prefix('notif')->middleware('jwt.auth')->group(function (): void {
            Route::get('/', [NotifController::class, 'getNotif']);
            Route::get('/count', [NotifController::class, 'countNotif']);
            Route::patch('/', [NotifController::class, 'update']);
            Route::patch('/all', [NotifController::class, 'updateAllNotif']);
        });

        Route::prefix('notif-admin')->middleware('jwt.auth')->group(function (): void {
            Route::get('/', [NotifAdminController::class, 'index']);
            Route::get('/mitra-recipient', [NotifAdminController::class, 'getMitraRecipient']);
            Route::post('/', [NotifAdminController::class, 'createNotifAdmin']);
            Route::get('/{id}', [NotifAdminController::class, 'getById']);
        });

        Route::post('/admin-users/register', [UserAdminController::class, 'storeRegister']);
        Route::post('/mitra-users/register', [UserMitraController::class, 'storeRegister']);

        Route::prefix('admin-users')->middleware('jwt.auth')->group(function (): void {
            Route::get('/', [UserAdminController::class, 'index']);
            Route::get('/list', [UserAdminController::class, 'getUsersByRole']);
            Route::get('/verification', [UserAdminController::class, 'getDataVerification']);
            Route::get('/role-list', [UserAdminController::class, 'getRoleList']);
            Route::get('/mitra-list', [UserAdminController::class, 'getAdminMitraList']);
            Route::get('/{userId}', [UserAdminController::class, 'getDataById']);
            Route::put('/{userId}', [UserAdminController::class, 'updateAdminByUserId']);
            Route::patch('/{userId}/status-approval', [UserAdminController::class, 'updateStatusApproval']);
            Route::patch('/{userId}/status', [UserAdminController::class, 'updateStatus']);
            Route::delete('/{userId}', [UserAdminController::class, 'deleteUser']);
            Route::post('/change-password', [UserAdminController::class, 'changePassword']);
        });

        Route::prefix('mitra-users')->middleware('jwt.auth')->group(function (): void {
            Route::get('/', [UserMitraController::class, 'index']);
            Route::get('/list', [UserMitraController::class, 'getUsersByRole']);
            Route::get('/verification', [UserMitraController::class, 'getDataVerification']);
            Route::post('/', [UserMitraController::class, 'store']);
            Route::post('/upload-excel', [UserMitraController::class, 'uploadExcel']);
            Route::post('/change-password', [UserMitraController::class, 'changePassword']);
            Route::get('/{userId}', [UserMitraController::class, 'getDataById']);
            Route::put('/{userId}', [UserMitraController::class, 'updateByUserId']);
            Route::patch('/{userId}/status', [UserMitraController::class, 'updateStatus']);
            Route::patch('/{userId}/status-approval', [UserMitraController::class, 'updateStatusApproval']);
            Route::delete('/{userId}', [UserMitraController::class, 'deleteUser']);
        });
    });
