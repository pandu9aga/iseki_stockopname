<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login/member', [AuthController::class, 'loginMember'])->name('login.member');
Route::post('/login/admin', [AuthController::class, 'loginAdmin'])->name('login.admin');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth:member')->group(function () {
    Route::get('/dashboard', [RecordController::class, 'index'])->name('dashboard');
    Route::get('/record', [RecordController::class, 'create'])->name('record.create');
    Route::post('/record', [RecordController::class, 'store'])->name('record.store');
});

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/dashboard', [RecordController::class, 'adminIndex'])->name('admin.dashboard');
    Route::get('/admin/export', [RecordController::class, 'export'])->name('admin.export');
    Route::resource('/admin/users', AdminController::class)->names('admin.users');
});
