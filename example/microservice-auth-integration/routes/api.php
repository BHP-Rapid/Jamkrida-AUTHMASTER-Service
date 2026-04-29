<?php

use App\Http\Controllers\Api\PenjaminanController;
use Illuminate\Support\Facades\Route;

Route::prefix('penjaminan')->group(function (): void {
    Route::get('/', [PenjaminanController::class, 'index'])
        ->middleware([
            'auth.context',
            'auth.permission:PENJAMINAN,view',
        ]);

    Route::post('/create', [PenjaminanController::class, 'store'])
        ->middleware([
            'auth.context',
            'auth.role:admin,super_admin,admin_mitra',
            'auth.permission:PENJAMINAN,create',
        ]);

    Route::put('/{id}', [PenjaminanController::class, 'update'])
        ->middleware([
            'auth.context',
            'auth.permission:PENJAMINAN,edit',
            'auth.permission:PENJAMINAN,create',
        ]);

    Route::delete('/{id}', [PenjaminanController::class, 'destroy'])
        ->middleware([
            'auth.context',
            'auth.permission:PENJAMINAN,delete',
        ]);
});
