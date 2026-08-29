<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MaintenanceNoticeController;
use App\Models\Module;
use App\Models\SystemModule;
use App\Support\RoleHomeResolver;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GA\GaRequestController;
use App\Http\Controllers\GA\GaQuickRequestController;
use App\Http\Controllers\GA\AssetController;
use App\Http\Controllers\GA\MaintenanceJobController;
use App\Http\Controllers\GA\DashboardController;
use App\Http\Controllers\GA\AssetQrController;
use App\Http\Controllers\GA\UniformStockController;
use App\Http\Controllers\GA\UniformMovementController;
use App\Http\Controllers\GA\UniformRecordController;
use App\Http\Controllers\GA\WorkLogController;
use App\Http\Controllers\GA\OutletInspectionAreaController;
use App\Http\Controllers\Head\HeadController;
use App\Http\Controllers\Head\HeadAssetController;
use App\Http\Controllers\Head\HeadUniformController;
use App\Http\Controllers\Head\HeadMaintenanceController;
use App\Http\Controllers\Head\HeadModuleController;
use App\Http\Controllers\IT\ModuleControlController;
use App\Http\Controllers\IT\ItBoardController;
use App\Http\Controllers\IT\ItTaskController;
use App\Http\Controllers\IT\ItTaskChecklistController;
use App\Http\Controllers\IT\ItTaskCommentController;
use App\Http\Controllers\IT\ItTaskLabelController;
use App\Http\Controllers\IT\UserManagementController;
use App\Http\Controllers\Outlet\OutletController;

// Dulu langsung view('welcome') bawaan Laravel (halaman dev "Let's get
// started" tidak dibranding) — sekarang diarahkan spt /login yg sudah
// authenticated (lihat RedirectIfAuthenticated override di
// AppServiceProvider & RedirectsToRoleHome), supaya link apa pun yg
// mengarah ke '/' (mis. logo di layouts/guest.blade.php) selalu mendarat
// di halaman yang benar, bukan halaman default framework.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(RoleHomeResolver::routeNameFor(auth()->user()))
        : redirect()->route('login');
});

// Halaman publik read-only untuk QR fisik yang ditempel di aset — tidak butuh
// login supaya siapa pun yang scan (termasuk yang belum masuk sistem) langsung
// lihat info identifikasi aset. Di-throttle karena kode aset bisa ditebak.
Route::get('/a/{asset:asset_code}', [AssetQrController::class, 'publicShow'])
    ->name('assets.public')
    ->middleware('throttle:60,1');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Halaman "Sedang Dalam Pemeliharaan" — tujuan redirect
    // CheckModuleMaintenance middleware saat sebuah halaman ditandai IT
    // sedang dalam pemeliharaan & user yang akses bukan IT/Admin.
    Route::get('/maintenance/{key}', [MaintenanceNoticeController::class, 'show'])->name('maintenance.notice');
});

// Satu-satunya dashboard untuk role GA/Admin (dulu ada 2: placeholder
// generic '/dashboard' dan '/ga/dashboard' — sekarang digabung jadi 1).
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'role:GA,Admin'])
    ->name('dashboard');

Route::middleware(['auth', 'role:GA,Admin'])
    ->prefix('ga')
    ->name('ga.')
    ->group(function () {
        // Setiap sub-grup ditandai module:<key> supaya Head bisa
        // aktif/nonaktifkan & atur akses role per modul lewat halaman
        // Kontrol Modul (lihat HeadModuleController) tanpa perlu deploy ulang.
        Route::middleware(['module:'.Module::REQUESTS, 'module.maintenance:'.SystemModule::GA_REQUESTS])->group(function () {
            Route::resource('requests', GaRequestController::class)
                ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
                ->parameter('requests', 'gaRequest');
            Route::get('requests/{gaRequest}/document', [GaRequestController::class, 'document'])->name('requests.document');
            Route::post('requests/quick', [GaQuickRequestController::class, 'store'])->name('requests.quick.store');
        });

        Route::middleware(['module:'.Module::ASSETS, 'module.maintenance:'.SystemModule::GA_ASSETS])->group(function () {
            // Label QR aset — didaftarkan sebelum resource supaya "qr-labels" tidak
            // tertangkap sebagai parameter {asset} pada route show.
            Route::get('assets/qr-labels', [AssetQrController::class, 'bulk'])->name('assets.qr-labels');
            Route::get('assets/{asset}/qr', [AssetQrController::class, 'show'])->name('assets.qr');
            Route::get('assets/export/xlsx', [AssetController::class, 'exportExcel'])->name('assets.export.xlsx');
            Route::get('assets/export/pdf', [AssetController::class, 'exportPdf'])->name('assets.export.pdf');

            Route::resource('assets', AssetController::class);
        });

        // Tahap 5 — Inventaris Seragam
        Route::middleware(['module:'.Module::UNIFORMS, 'module.maintenance:'.SystemModule::GA_UNIFORMS])->group(function () {
            Route::get('uniforms/stocks/export/xlsx', [UniformStockController::class, 'exportExcel'])->name('uniforms.stocks.export.xlsx');
            Route::get('uniforms/stocks/export/pdf', [UniformStockController::class, 'exportPdf'])->name('uniforms.stocks.export.pdf');
            Route::delete('uniforms/stocks/group', [UniformStockController::class, 'destroyGroup'])->name('uniforms.stocks.destroy-group');
            Route::get('uniforms/stocks/group', [UniformStockController::class, 'showGroup'])->name('uniforms.stocks.show-group');
            Route::get('uniforms/stocks/group/edit', [UniformStockController::class, 'editGroup'])->name('uniforms.stocks.edit-group');
            Route::put('uniforms/stocks/group', [UniformStockController::class, 'updateGroup'])->name('uniforms.stocks.update-group');
            Route::post('uniforms/stocks/{stock}/restock', [UniformStockController::class, 'restock'])->name('uniforms.stocks.restock');
            Route::resource('uniforms/stocks', UniformStockController::class)
                ->parameters(['stocks' => 'stock'])
                ->names('uniforms.stocks');

            Route::get('uniforms/movements', [UniformMovementController::class, 'index'])->name('uniforms.movements.index');

            Route::get('uniforms/records/export/xlsx', [UniformRecordController::class, 'exportExcel'])->name('uniforms.records.export.xlsx');
            Route::get('uniforms/records/export/pdf', [UniformRecordController::class, 'exportPdf'])->name('uniforms.records.export.pdf');
            Route::post('uniforms/records/{record}/return', [UniformRecordController::class, 'markReturned'])->name('uniforms.records.return');
            Route::get('uniforms/records/{record}/document', [UniformRecordController::class, 'document'])->name('uniforms.records.document');
            Route::get('uniforms/records/{record}/return-document', [UniformRecordController::class, 'returnDocument'])->name('uniforms.records.return-document');
            Route::resource('uniforms/records', UniformRecordController::class)
                ->parameters(['records' => 'record'])
                ->names('uniforms.records');
        });

        // Tahap 4 — Jadwal Pemeliharaan
        Route::middleware(['module:'.Module::MAINTENANCE, 'module.maintenance:'.SystemModule::GA_MAINTENANCE])->group(function () {
            Route::post('maintenance/{maintenance}/complete', [MaintenanceJobController::class, 'complete'])
                ->name('maintenance.complete');
            Route::resource('maintenance', MaintenanceJobController::class);
        });

        // Work Log — catatan aktivitas teknisi per outlet (03/08/2026),
        // tanpa approval, independen dari Asset/MaintenanceJob.
        Route::middleware(['module:'.Module::WORK_LOG, 'module.maintenance:'.SystemModule::GA_WORKLOG])->group(function () {
            Route::delete('worklogs/{worklog}/attachments/{attachment}', [WorkLogController::class, 'destroyAttachment'])->name('worklogs.attachments.destroy');
            Route::resource('worklogs', WorkLogController::class);
        });

        // Area Pemeriksaan per outlet (Monitoring Outlet — Fase B-1, 24/08/2026)
        // — CRUD area dikelola GA, prasyarat form laporan foto per area
        // (Fase B-2, belum dibangun). Bukan Route::resource (nested di bawah
        // {branch}, plus aksi toggle custom), didaftarkan manual satu-satu.
        Route::middleware(['module:'.Module::OUTLET_MONITORING, 'module.maintenance:'.SystemModule::GA_OUTLET_MONITORING])->group(function () {
            Route::get('outlet-areas', [OutletInspectionAreaController::class, 'index'])->name('outlet-areas.index');
            Route::get('outlet-areas/{branch}', [OutletInspectionAreaController::class, 'manage'])->name('outlet-areas.manage');
            Route::post('outlet-areas/{branch}', [OutletInspectionAreaController::class, 'store'])->name('outlet-areas.store');
            Route::put('outlet-areas/{branch}/{area}', [OutletInspectionAreaController::class, 'update'])->name('outlet-areas.update');
            Route::post('outlet-areas/{branch}/{area}/toggle', [OutletInspectionAreaController::class, 'toggleActive'])->name('outlet-areas.toggle');
            Route::patch('outlet-areas/{branch}/{area}/reorder', [OutletInspectionAreaController::class, 'reorder'])->name('outlet-areas.reorder');
            Route::delete('outlet-areas/{branch}/{area}', [OutletInspectionAreaController::class, 'destroy'])->name('outlet-areas.destroy');
        });
    });

// Dashboard Head — terpisah total dari grup ga. di atas (sidebar & view sendiri),
// hanya reuse model/logic approval yang sudah ada (lihat HeadController).
Route::middleware(['auth', 'role:Head'])
    ->prefix('head')
    ->name('head.')
    ->group(function () {
        // Setiap section ditandai module.maintenance:<key> supaya IT bisa
        // menandai halaman ini "Dalam Pemeliharaan" tanpa menyentuh logic
        // route di bawahnya (lihat CheckModuleMaintenance).
        Route::middleware('module.maintenance:'.SystemModule::HEAD_DASHBOARD)->group(function () {
            Route::get('dashboard', [HeadController::class, 'dashboard'])->name('dashboard');
        });

        Route::middleware('module.maintenance:'.SystemModule::HEAD_REQUESTS)->group(function () {
            // Pengajuan (dashboard approval)
            Route::get('requests', [HeadController::class, 'requestsIndex'])->name('requests.index');
            Route::get('requests/{gaRequest}', [HeadController::class, 'showRequest'])->name('requests.show');
            Route::post('requests/{gaRequest}/approve', [HeadController::class, 'approve'])->name('requests.approve');
            Route::post('requests/{gaRequest}/reject', [HeadController::class, 'reject'])->name('requests.reject');
        });

        // Kontrol Modul — aktif/nonaktifkan modul (atur akses role per modul
        // dipindah ke modul role IT terpisah, tidak ada di sini).
        Route::middleware('module.maintenance:'.SystemModule::HEAD_MODULES)->group(function () {
            Route::get('modules', [HeadModuleController::class, 'index'])->name('modules.index');
            Route::post('modules/{module}/toggle', [HeadModuleController::class, 'toggle'])->name('modules.toggle');
        });

        // Aset (monitoring read-only)
        Route::middleware('module.maintenance:'.SystemModule::HEAD_ASSETS)->group(function () {
            Route::get('assets', [HeadAssetController::class, 'index'])->name('assets.index');
        });

        // Seragam (monitoring read-only)
        Route::middleware('module.maintenance:'.SystemModule::HEAD_UNIFORMS)->group(function () {
            Route::get('uniforms', [HeadUniformController::class, 'index'])->name('uniforms.index');
        });

        // Jadwal Pemeliharaan (monitoring read-only)
        Route::middleware('module.maintenance:'.SystemModule::HEAD_MAINTENANCE)->group(function () {
            Route::get('maintenance', [HeadMaintenanceController::class, 'index'])->name('maintenance.index');
        });

    });

// Portal Outlet — terpisah total dari grup ga./head. di atas (sidebar &
// view sendiri, pola sama grup head.), TAPI menunjuk ke controller CLASS
// YANG SAMA persis dengan grup ga. (bukan controller baru) — supaya query/
// validasi/CRUD tidak diduplikasi, dan supaya kalau nanti ada user GA yang
// di-set tier "Lihat saja", guard tier di controller yang sama juga berlaku
// utknya (lihat User::canEdit(), UserPagePermission). Branch di-hard-scope
// otomatis di controller berdasar $user->branch (bukan filter opsional
// seperti grup head.) — lihat masing2 controller GA.
Route::middleware(['auth', 'role:Outlet', 'outlet.branch'])
    ->prefix('outlet')
    ->name('outlet.')
    ->group(function () {
        Route::get('dashboard', [OutletController::class, 'dashboard'])->name('dashboard');

        Route::middleware(['module:'.Module::REQUESTS, 'module.maintenance:'.SystemModule::GA_REQUESTS])->group(function () {
            Route::resource('requests', GaRequestController::class)
                ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
                ->parameter('requests', 'gaRequest');
            Route::get('requests/{gaRequest}/document', [GaRequestController::class, 'document'])->name('requests.document');
            Route::post('requests/quick', [GaQuickRequestController::class, 'store'])->name('requests.quick.store');
        });

        Route::middleware(['module:'.Module::ASSETS, 'module.maintenance:'.SystemModule::GA_ASSETS])->group(function () {
            Route::resource('assets', AssetController::class)->only(['index', 'show']);
        });

        Route::middleware(['module:'.Module::UNIFORMS, 'module.maintenance:'.SystemModule::GA_UNIFORMS])->group(function () {
            Route::resource('uniforms/stocks', UniformStockController::class)
                ->parameters(['stocks' => 'stock'])
                ->only(['index', 'show'])
                ->names('uniforms.stocks');
            Route::resource('uniforms/records', UniformRecordController::class)
                ->parameters(['records' => 'record'])
                ->only(['index', 'create', 'store', 'show'])
                ->names('uniforms.records');
            Route::post('uniforms/records/{record}/return', [UniformRecordController::class, 'markReturned'])->name('uniforms.records.return');
        });

        Route::middleware(['module:'.Module::MAINTENANCE, 'module.maintenance:'.SystemModule::GA_MAINTENANCE])->group(function () {
            Route::resource('maintenance', MaintenanceJobController::class)->only(['index', 'show']);
        });

        Route::middleware(['module:'.Module::WORK_LOG, 'module.maintenance:'.SystemModule::GA_WORKLOG])->group(function () {
            Route::resource('worklogs', WorkLogController::class)->only(['index', 'show']);
        });
    });

// Kontrol Akses & Mode Pemeliharaan — khusus role IT, terpisah total dari
// grup ga./head. di atas (sidebar & view sendiri), sama seperti pola grup
// head. dipisah dari ga. sebelumnya.
Route::middleware(['auth', 'role:IT'])
    ->prefix('it')
    ->name('it.')
    ->group(function () {
        Route::get('modules', [ModuleControlController::class, 'index'])->name('modules.index');
        Route::post('modules/{systemModule}/toggle', [ModuleControlController::class, 'toggle'])->name('modules.toggle');

        // Papan Kerja Kanban — bug fix, pengembangan fitur, dst (lihat
        // ItBoardController). Semua endpoint task/checklist/comment JSON,
        // dipanggil via fetch dari board Kanban (drag & drop + modal detail).
        Route::get('board', [ItBoardController::class, 'index'])->name('board.index');

        Route::post('tasks', [ItTaskController::class, 'store'])->name('tasks.store');
        Route::get('tasks/{itTask}', [ItTaskController::class, 'show'])->name('tasks.show');
        Route::patch('tasks/{itTask}', [ItTaskController::class, 'update'])->name('tasks.update');
        Route::delete('tasks/{itTask}', [ItTaskController::class, 'destroy'])->name('tasks.destroy');

        Route::post('tasks/{itTask}/checklist', [ItTaskChecklistController::class, 'store'])->name('tasks.checklist.store');
        Route::patch('tasks/{itTask}/checklist/{item}', [ItTaskChecklistController::class, 'update'])->name('tasks.checklist.update');
        Route::delete('tasks/{itTask}/checklist/{item}', [ItTaskChecklistController::class, 'destroy'])->name('tasks.checklist.destroy');

        Route::post('tasks/{itTask}/comments', [ItTaskCommentController::class, 'store'])->name('tasks.comments.store');

        Route::get('labels', [ItTaskLabelController::class, 'index'])->name('labels.index');
        Route::post('labels', [ItTaskLabelController::class, 'store'])->name('labels.store');

        // Manajemen User — satu-satunya jalur bikin akun baru (/register
        // publik sudah dihapus, lihat routes/auth.php). Lihat
        // UserManagementController utk detail module_user vs module_role.
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });

require __DIR__.'/auth.php';
