<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditorController;
use App\Http\Controllers\AuditorPortalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaseDataController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\DualCheckController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\ManualCountController;
use App\Http\Controllers\MissingController;
use App\Http\Controllers\PartMonitoringController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\RoleSelectionController;
use App\Http\Controllers\ScaleController;
use App\Http\Controllers\SoEventController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/page/login', [AuthController::class, 'pageLogin'])->name('page.login');
Route::post('/login/member', [AuthController::class, 'loginMember'])->name('login.member');
Route::post('/login/admin', [AuthController::class, 'loginAdmin'])->name('login.admin');
Route::post('/login/auditor', [AuthController::class, 'loginAuditor'])->name('login.auditor');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/page/dashboard', [MainController::class, 'index'])->name('page.dashboard');
Route::get('/page/record', [MainController::class, 'create'])->name('page.record.create');
Route::post('/page/record', [MainController::class, 'store'])->name('page.record.store');

// Accessible to any authenticated user (member or admin)
Route::get('/records/{record}', [RecordController::class, 'show'])->name('records.show');

// ── Endpoint Data AJAX Parts & Event SO (Bisa diakses Member maupun Admin) ──
Route::middleware('auth:member,admin')->group(function () {
    Route::get('/parts/data/{id?}', [PartMonitoringController::class, 'data'])->name('parts.data');
});

Route::middleware('auth:member')->group(function () {
    // Role Switcher Foundation
    Route::get('/member/select-role', [RoleSelectionController::class, 'index'])->name('member.select-role');
    Route::post('/member/switch-role', [RoleSelectionController::class, 'switchRole'])->name('member.switch-role');
    Route::get('/member/role-redirect', [RoleSelectionController::class, 'redirectByRole'])->name('member.role.redirect');

    // Fase 2A: Kurir Module
    Route::get('/courier', [CourierController::class, 'index'])->name('courier.index');
    Route::get('/courier/lookup-rack', [CourierController::class, 'lookupRack'])->name('courier.lookup-rack');
    Route::post('/courier/store', [CourierController::class, 'store'])->name('courier.store');
    // ── Fase 2B: Modul Timbangan (Scale) ──
    Route::prefix('scale')->name('scale.')->group(function () {
        Route::get('/',                   [ScaleController::class, 'index'])->name('index');
        Route::get('/search-partners',    [ScaleController::class, 'searchPartners'])->name('search-partners');
        Route::post('/validate-partner',  [ScaleController::class, 'validatePartner'])->name('validate-partner');
        Route::post('/setup-partner',     [ScaleController::class, 'setupPartner'])->name('setup-partner');
        Route::post('/lookup-item',       [ScaleController::class, 'lookupItem'])->name('lookup-item');
        Route::post('/sample-qty',        [ScaleController::class, 'submitSampleQty'])->name('sample-qty');
        Route::post('/sample-round',      [ScaleController::class, 'submitSampleRound'])->name('sample-round');
        Route::post('/submit-final',      [ScaleController::class, 'submitFinalWeighing'])->name('submit-final');
        Route::post('/mark-printed',      [ScaleController::class, 'markPrinted'])->name('mark-printed');
        Route::post('/label-proof',       [ScaleController::class, 'uploadLabelProof'])->name('label-proof');
        Route::post('/cancel-session',    [ScaleController::class, 'cancelSession'])->name('cancel-session');
        Route::get('/item-history',       [ScaleController::class, 'itemHistory'])->name('item-history');
        Route::get('/reprint',            [ScaleController::class, 'reprintIndex'])->name('reprint');
        Route::post('/reprint-lookup',    [ScaleController::class, 'reprintLookup'])->name('reprint-lookup');
    });

    // ── Fase 3B: Modul Non-Timbangan (Pos 1 & Pos 2 Manual) ──
    Route::prefix('manual')->name('manual.')->group(function () {
        // Pos 1
        Route::get('/pos1',           [ManualCountController::class, 'pos1Index'])->name('pos1.index');
        Route::post('/pos1/submit',   [ManualCountController::class, 'submitPos1'])->name('pos1.submit');

        // Pos 2
        Route::get('/pos2',               [ManualCountController::class, 'pos2Index'])->name('pos2.index');
        Route::post('/pos2/submit',       [ManualCountController::class, 'submitPos2'])->name('pos2.submit');
        Route::post('/pos2/upload-final', [ManualCountController::class, 'uploadFinalPhoto'])->name('pos2.upload-final');

        // Shared lookup & history
        Route::post('/lookup-item',   [ManualCountController::class, 'lookupItem'])->name('lookup-item');
        Route::get('/item-history',   [ManualCountController::class, 'itemHistory'])->name('item-history');
    });

    // ── Fase 3A: Modul Pengembalian (Return Module) ──
    Route::prefix('return')->name('return.')->group(function () {
        Route::get('/',                   [ReturnController::class, 'index'])->name('index');
        Route::post('/lookup-label',      [ReturnController::class, 'lookupFinalLabel'])->name('lookup-label');
        Route::post('/verify-rack',       [ReturnController::class, 'verifyRack'])->name('verify-rack');
        Route::post('/submit',            [ReturnController::class, 'submitReturn'])->name('submit');
    });

    // ── Monitoring Semua Part & Riwayat Tahap SO per Event ──
    Route::get('/member/parts',               [PartMonitoringController::class, 'index'])->name('member.parts.index');
    Route::get('/member/parts/event/{id}',    [PartMonitoringController::class, 'showEvent'])->name('member.parts.show-event');
    Route::get('/member/parts/data/{id?}',    [PartMonitoringController::class, 'data'])->name('member.parts.data');

    // Legacy Routes (Retained, hidden from main navigation)
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
    Route::resource('/admin/auditors', AuditorController::class)->names('admin.auditors');
    Route::resource('/admin/base-data', BaseDataController::class)->names('admin.base-data');
    Route::post('/admin/base-data/import', [BaseDataController::class, 'import'])->name('admin.base-data.import');
    Route::get('/admin/missing', [MissingController::class, 'index'])->name('admin.missing.index');
    Route::get('/admin/dual-check', [DualCheckController::class, 'adminIndex'])->name('admin.dual-check');

    // ── Fase 2B: Admin CRUD Master Timbangan ──
    Route::get('/admin/scales',         [ScaleController::class, 'adminIndex'])->name('admin.scales.index');
    Route::post('/admin/scales',        [ScaleController::class, 'adminStore'])->name('admin.scales.store');
    Route::put('/admin/scales/{scale}', [ScaleController::class, 'adminUpdate'])->name('admin.scales.update');
    Route::delete('/admin/scales/{scale}', [ScaleController::class, 'adminDestroy'])->name('admin.scales.destroy');

    // ── Master Jadwal & Event Stock Opname ──
    Route::get('/admin/so-events',             [SoEventController::class, 'index'])->name('admin.so-events.index');
    Route::post('/admin/so-events',            [SoEventController::class, 'store'])->name('admin.so-events.store');
    Route::get('/admin/so-events/{id}/parts',  [SoEventController::class, 'showEventParts'])->name('admin.so-events.parts');
    Route::post('/admin/so-events/parts/{id}/update-location', [PartMonitoringController::class, 'updatePartLocation'])->name('admin.so-events.parts.update-location');
    Route::post('/admin/so-events/{id}/activate', [SoEventController::class, 'activate'])->name('admin.so-events.activate');
    Route::post('/admin/so-events/{id}/close',    [SoEventController::class, 'close'])->name('admin.so-events.close');
    Route::delete('/admin/so-events/{id}',        [SoEventController::class, 'destroy'])->name('admin.so-events.destroy');
});

Route::middleware('auth:auditor')->group(function () {
    // ── Fase 4A: Modul Auditor & Unaudited List ──
    Route::get('/auditor/dashboard', [AuditorPortalController::class, 'index'])->name('auditor.dashboard');
    Route::get('/auditor/portal',    [AuditorPortalController::class, 'index'])->name('auditor.portal');
    Route::get('/auditor/unaudited', [AuditorPortalController::class, 'unauditedList'])->name('auditor.unaudited');
    Route::post('/auditor/lookup',   [AuditorPortalController::class, 'lookupRack'])->name('auditor.lookup-rack');
    Route::post('/auditor/submit',   [AuditorPortalController::class, 'submitAudit'])->name('auditor.submit-audit');
});
