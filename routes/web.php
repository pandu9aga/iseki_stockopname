<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaseDataController;
use App\Http\Controllers\DualCheckController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\MissingController;
use App\Http\Controllers\RecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/page/login', [AuthController::class, 'pageLogin'])->name('page.login');
Route::post('/login/member', [AuthController::class, 'loginMember'])->name('login.member');
Route::post('/login/admin', [AuthController::class, 'loginAdmin'])->name('login.admin');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/page/dashboard', [MainController::class, 'index'])->name('page.dashboard');
Route::get('/page/record', [MainController::class, 'create'])->name('page.record.create');
Route::post('/page/record', [MainController::class, 'store'])->name('page.record.store');

// Accessible to any authenticated user (member or admin)
Route::get('/records/{record}', [RecordController::class, 'show'])->name('records.show');

Route::middleware('auth:member')->group(function () {
    Route::get('/dashboard', [RecordController::class, 'index'])->name('dashboard');
    Route::get('/record', [RecordController::class, 'create'])->name('record.create');
    Route::post('/record', [RecordController::class, 'store'])->name('record.store');
    Route::get('/dual-check', [DualCheckController::class, 'index'])->name('dual-check.dashboard');
    Route::get('/dual-check/record', [DualCheckController::class, 'create'])->name('dual-check.create');
    Route::post('/dual-check/record', [DualCheckController::class, 'store'])->name('dual-check.store');
});

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/dashboard', [RecordController::class, 'adminIndex'])->name('admin.dashboard');
    Route::get('/admin/dashboard-no-count', [MainController::class, 'dashboardNoCount'])->name('admin.dashboard.nocount');
    // Route::get('/admin/export', [RecordController::class, 'export'])->name('admin.export');
    Route::get('/admin/export', [MainController::class, 'export'])->name('admin.export');
    Route::delete('/admin/records/{record}', [RecordController::class, 'destroy'])->name('admin.records.destroy');
    Route::resource('/admin/users', AdminController::class)->names('admin.users');
    Route::resource('/admin/base-data', BaseDataController::class)->names('admin.base-data');
    Route::post('/admin/base-data/import', [BaseDataController::class, 'import'])->name('admin.base-data.import');
    Route::get('/admin/missing', [MissingController::class, 'index'])->name('admin.missing.index');
    Route::get('/admin/dual-check', [DualCheckController::class, 'adminIndex'])->name('admin.dual-check');
});
