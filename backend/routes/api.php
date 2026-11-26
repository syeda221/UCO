<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CollectionSyncController;
use App\Http\Controllers\Api\MasterDataController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:collector'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/master-data', [MasterDataController::class, 'index']);

        Route::post('/collections/sync', [CollectionSyncController::class, 'sync']);
        Route::post('/collections/{clientEventUuid}/photos', [CollectionSyncController::class, 'uploadPhoto']);
        Route::get('/collections/{clientEventUuid}/status', [CollectionSyncController::class, 'status']);
        Route::get('/collections/my-history', [CollectionSyncController::class, 'myHistory']);
    });
});
