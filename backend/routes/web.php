<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CollectionEventController;
use App\Http\Controllers\Admin\CollectorController;
use App\Http\Controllers\Admin\ContainerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.dashboard'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::get('/vendors/{vendor}/docs/{index}', [VendorController::class, 'downloadDoc'])->name('vendors.doc');

        Route::get('/containers', [ContainerController::class, 'index'])->name('containers.index');
        Route::post('/containers', [ContainerController::class, 'store'])->name('containers.store');
        Route::post('/containers/batch', [ContainerController::class, 'generateBatch'])->name('containers.batch');
        Route::put('/containers/{container}', [ContainerController::class, 'update'])->name('containers.update');
        Route::get('/containers/print-qr', [ContainerController::class, 'printQrPdf'])->name('containers.print-qr');

        Route::get('/collectors', [CollectorController::class, 'index'])->name('collectors.index');
        Route::post('/collectors', [CollectorController::class, 'store'])->name('collectors.store');
        Route::put('/collectors/{collector}', [CollectorController::class, 'update'])->name('collectors.update');

        Route::get('/events', [CollectionEventController::class, 'index'])->name('events.index');
        Route::get('/events/{event}', [CollectionEventController::class, 'show'])->name('events.show');
        Route::post('/events/{event}/correction', [CollectionEventController::class, 'logCorrection'])->name('events.correction');
        Route::post('/events/{event}/set-vendor-gps', [CollectionEventController::class, 'setVendorGps'])->name('events.set-vendor-gps');
        Route::get('/photos/{photo}', [CollectionEventController::class, 'showPhoto'])->name('photos.show');
    });
