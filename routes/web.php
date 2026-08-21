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
use App\Http\Controllers\Head\HeadController;
use App\Http\Controllers\Head\HeadAssetController;
use App\Http\Controllers\Head\HeadUniformController;
use App\Http\Controllers\Head\HeadMaintenanceController;
use App\Http\Controllers\Head\HeadModuleController;
use App\Http\Controllers\Head\HeadApprovalController;
use App\Http\Controllers\IT\ModuleControlController;
use App\Http\Controllers\IT\ItBoardController;
use App\Http\Controllers\IT\ItTaskController;
use App\Http\Controllers\IT\ItTaskChecklistController;
use App\Http\Controllers\IT\ItTaskCommentController;
use App\Http\Controllers\IT\ItTaskLabelController;
use App\Http\Controllers\IT\DesignSystemController;
use App\Http\Controllers\IT\UserManagementController;
use App\Http\Controllers\SCM\MaterialRequestController;
use App\Http\Controllers\SCM\ProductionBatchController;
use App\Http\Controllers\SCM\BatchLabelController;
use App\Http\Controllers\SCM\DeliveryNoteController;
use App\Http\Controllers\SCM\DeliveryReceiptController;
use App\Http\Controllers\SCM\DiscrepancyReportController;
use App\Http\Controllers\SCM\ScmReportController;
use App\Http\Controllers\Head\HeadScmController;
use App\Http\Controllers\Purchasing\SupplierController;
use App\Http\Controllers\Purchasing\PurchaseRequisitionController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\GoodsReceiptController;
use App\Http\Controllers\Purchasing\SupplierInvoiceController;
use App\Http\Controllers\Finance\ChartOfAccountController;
use App\Http\Controllers\Finance\TransactionAccountMappingController;
use App\Http\Controllers\Finance\FinanceReportController;

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
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Halaman "Sedang Dalam Pemeliharaan" — tujuan redirect
    // CheckModuleMaintenance middleware saat sebuah halaman ditandai IT
    // sedang dalam pemeliharaan & user yang akses bukan IT/Admin.
    Route::get('/maintenance/{key}', [MaintenanceNoticeController::class, 'show'])->name('maintenance.notice');
});

// Satu-satunya dashboard untuk role GA/Admin/Finance (dulu ada 2: placeholder
// generic '/dashboard' dan '/ga/dashboard' — sekarang digabung jadi 1).
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'role:GA,Admin,Finance'])
    ->name('dashboard');

Route::middleware(['auth', 'role:GA,Admin,Finance'])
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

        // Approval Inbox — generik lintas modul (approvable_type apapun),
        // dibangun di atas tabel approvals & trait Approvable yang sama.
        Route::middleware('module.maintenance:'.SystemModule::HEAD_APPROVALS)->group(function () {
            Route::get('approvals', [HeadApprovalController::class, 'index'])->name('approvals.index');
            Route::post('approvals/{approval}/approve', [HeadApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('approvals/{approval}/reject', [HeadApprovalController::class, 'reject'])->name('approvals.reject');
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

        // SCM (monitoring read-only) — rekap periodik + laporan selisih,
        // reuse query ScmReportController (lihat HeadScmController).
        Route::middleware('module.maintenance:'.SystemModule::HEAD_SCM)->group(function () {
            Route::get('scm', [HeadScmController::class, 'index'])->name('scm.index');
        });
    });

// Modul SCM (Supply Chain / Warehouse) — Pengajuan Bahan -> Batch Produksi ->
// Cetak Label -> Surat Jalan -> Terima Outlet -> Laporan Selisih otomatis.
// Role Head SENGAJA tidak dimasukkan ke grup ini — Head punya halaman
// monitoring read-only terpisah (head.scm.index di atas), sama seperti pola
// pemisahan Head dari grup ga. untuk tiap sub-modul GA.
Route::middleware(['auth', 'role:Produksi,Gudang,Outlet,Admin'])
    ->prefix('scm')
    ->name('scm.')
    ->group(function () {
        // Pengajuan Bahan + Batch Produksi + Cetak Label — 1 toggle Head,
        // tapi tiap halaman tetap punya key mode-maintenance IT sendiri.
        Route::middleware(['module:'.Module::SCM_MATERIALS, 'module.maintenance:'.SystemModule::SCM_MATERIALS])->group(function () {
            Route::get('materials', [MaterialRequestController::class, 'index'])->name('materials.index');
            Route::get('materials/create', [MaterialRequestController::class, 'create'])->name('materials.create');
            Route::post('materials', [MaterialRequestController::class, 'store'])->name('materials.store');
            Route::get('materials/{materialRequest}', [MaterialRequestController::class, 'show'])->name('materials.show');
            Route::post('materials/{materialRequest}/approve', [MaterialRequestController::class, 'approve'])->name('materials.approve');
            Route::post('materials/{materialRequest}/reject', [MaterialRequestController::class, 'reject'])->name('materials.reject');
            Route::get('materials/{materialRequest}/document', [MaterialRequestController::class, 'document'])->name('materials.document');
        });

        Route::middleware(['module:'.Module::SCM_MATERIALS, 'module.maintenance:'.SystemModule::SCM_BATCHES])->group(function () {
            Route::get('batches/export/xlsx', [ProductionBatchController::class, 'exportExcel'])->name('batches.export.xlsx');
            Route::get('batches/export/pdf', [ProductionBatchController::class, 'exportPdf'])->name('batches.export.pdf');
            Route::get('batches', [ProductionBatchController::class, 'index'])->name('batches.index');
            Route::get('batches/create', [ProductionBatchController::class, 'create'])->name('batches.create');
            Route::post('batches', [ProductionBatchController::class, 'store'])->name('batches.store');
            Route::get('batches/{productionBatch}', [ProductionBatchController::class, 'show'])->name('batches.show');
            Route::post('batches/{productionBatch}/approve', [ProductionBatchController::class, 'approve'])->name('batches.approve');
            Route::post('batches/{productionBatch}/reject', [ProductionBatchController::class, 'reject'])->name('batches.reject');

            Route::post('batches/{productionBatch}/labels', [BatchLabelController::class, 'store'])->name('batches.labels.store');
            Route::get('batches/{productionBatch}/labels', [BatchLabelController::class, 'print'])->name('batches.labels.print');
        });

        // Surat Jalan + Terima Outlet
        Route::middleware(['module:'.Module::SCM_DELIVERIES, 'module.maintenance:'.SystemModule::SCM_DELIVERIES])->group(function () {
            Route::get('deliveries', [DeliveryNoteController::class, 'index'])->name('deliveries.index');
            Route::get('deliveries/create', [DeliveryNoteController::class, 'create'])->name('deliveries.create');
            // Statis, wajib terdaftar SEBELUM deliveries/{deliveryNote} di
            // bawah — kalau tidak, "available-labels" akan ke-bind sebagai
            // parameter {deliveryNote} (route-model-binding).
            Route::get('deliveries/available-labels', [DeliveryNoteController::class, 'availableLabels'])->name('deliveries.available-labels');
            Route::post('deliveries', [DeliveryNoteController::class, 'store'])->name('deliveries.store');
            Route::get('deliveries/{deliveryNote}', [DeliveryNoteController::class, 'show'])->name('deliveries.show');
            Route::post('deliveries/{deliveryNote}/send', [DeliveryNoteController::class, 'send'])->name('deliveries.send');
            Route::get('deliveries/{deliveryNote}/document', [DeliveryNoteController::class, 'document'])->name('deliveries.document');

            Route::post('deliveries/{deliveryNote}/receive', [DeliveryReceiptController::class, 'store'])->name('deliveries.receive');
            Route::get('deliveries/{deliveryNote}/berita-acara', [DeliveryReceiptController::class, 'document'])->name('deliveries.berita-acara');
        });

        // Laporan Selisih (otomatis) + Rekap Periodik
        Route::middleware(['module:'.Module::SCM_REPORTS, 'module.maintenance:'.SystemModule::SCM_DISCREPANCIES])->group(function () {
            Route::get('discrepancies', [DiscrepancyReportController::class, 'index'])->name('discrepancies.index');
            Route::get('discrepancies/{discrepancyReport}/document', [DiscrepancyReportController::class, 'document'])->name('discrepancies.document');
        });

        Route::middleware(['module:'.Module::SCM_REPORTS, 'module.maintenance:'.SystemModule::SCM_REPORTS])->group(function () {
            Route::get('reports', [ScmReportController::class, 'index'])->name('reports.index');
            Route::get('reports/pdf', [ScmReportController::class, 'exportPdf'])->name('reports.pdf');
        });

        // Fase 1: Stok Real-Time & FEFO — stok mendekati ED + nilai persediaan.
        // near-expiry SENGAJA pakai Module key sendiri (bukan SCM_REPORTS)
        // karena controller-nya juga mengizinkan Gudang, beda dgn rekap/
        // discrepancies/stock-value yang Admin-only.
        Route::middleware(['module:'.Module::SCM_NEAR_EXPIRY, 'module.maintenance:'.SystemModule::SCM_NEAR_EXPIRY])->group(function () {
            Route::get('reports/near-expiry', [ScmReportController::class, 'nearExpiry'])->name('reports.near-expiry');
            Route::get('reports/near-expiry/export/xlsx', [ScmReportController::class, 'nearExpiryExportExcel'])->name('reports.near-expiry.export.xlsx');
            Route::get('reports/near-expiry/export/pdf', [ScmReportController::class, 'nearExpiryExportPdf'])->name('reports.near-expiry.export.pdf');
        });

        Route::middleware(['module:'.Module::SCM_REPORTS, 'module.maintenance:'.SystemModule::SCM_STOCK_VALUE])->group(function () {
            Route::get('reports/stock-value', [ScmReportController::class, 'stockValue'])->name('reports.stock-value');
            Route::get('reports/stock-value/export/xlsx', [ScmReportController::class, 'stockValueExportExcel'])->name('reports.stock-value.export.xlsx');
            Route::get('reports/stock-value/export/pdf', [ScmReportController::class, 'stockValueExportPdf'])->name('reports.stock-value.export.pdf');
        });
    });

// Modul Purchasing — 2 kategori PO: 'food' (bahan makanan, lewat Purchase
// Requisition dari Outlet -> approve Purchasing -> Purchasing bikin PO) &
// 'general' (barang umum, dibuat langsung GA). Keduanya lanjut approval
// Head lalu Finance, baru barang dibeli & diterima. Head SENGAJA tidak
// dimasukkan ke grup ini — approve lewat Approval Inbox generik yang sudah
// ada (head.approvals.*), sama seperti pola grup scm./ga. di atas.
Route::middleware(['auth', 'role:Purchasing,Outlet,GA,Finance,Gudang,Admin'])
    ->prefix('purchasing')
    ->name('purchasing.')
    ->group(function () {
        Route::middleware(['module.maintenance:'.SystemModule::PURCHASING_SUPPLIERS])->group(function () {
            Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
            Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
            Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
            Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        });

        // Purchase Requisition — diajukan Outlet, disetujui Purchasing,
        // dasar pembuatan PO kategori 'food' (lihat createFromRequisition di bawah).
        Route::middleware(['module:'.Module::PURCHASING_REQUISITIONS, 'module.maintenance:'.SystemModule::PURCHASING_REQUISITIONS])->group(function () {
            Route::get('purchase-requisitions', [PurchaseRequisitionController::class, 'index'])->name('purchase-requisitions.index');
            Route::get('purchase-requisitions/create', [PurchaseRequisitionController::class, 'create'])->name('purchase-requisitions.create');
            Route::post('purchase-requisitions', [PurchaseRequisitionController::class, 'store'])->name('purchase-requisitions.store');
            Route::get('purchase-requisitions/{purchaseRequisition}', [PurchaseRequisitionController::class, 'show'])->name('purchase-requisitions.show');
            Route::post('purchase-requisitions/{purchaseRequisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('purchase-requisitions.approve');
            Route::post('purchase-requisitions/{purchaseRequisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('purchase-requisitions.reject');
        });

        Route::middleware(['module:'.Module::PURCHASING_ORDERS, 'module.maintenance:'.SystemModule::PURCHASING_ORDERS])->group(function () {
            Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
            Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
            Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
            Route::get('purchase-orders/create-from-requisition/{purchaseRequisition}', [PurchaseOrderController::class, 'createFromRequisition'])->name('purchase-orders.create-from-requisition');
            Route::post('purchase-orders/from-requisition/{purchaseRequisition}', [PurchaseOrderController::class, 'storeFromRequisition'])->name('purchase-orders.store-from-requisition');
            Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
            Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
            Route::post('purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
        });

        Route::middleware(['module:'.Module::PURCHASING_RECEIPTS, 'module.maintenance:'.SystemModule::PURCHASING_RECEIPTS])->group(function () {
            Route::post('purchase-orders/{purchaseOrder}/receipt', [GoodsReceiptController::class, 'store'])->name('purchase-orders.receipt.store');
        });

        Route::middleware(['module:'.Module::PURCHASING_RECEIPTS, 'module.maintenance:'.SystemModule::PURCHASING_INVOICES])->group(function () {
            Route::get('supplier-invoices', [SupplierInvoiceController::class, 'index'])->name('supplier-invoices.index');
            Route::post('goods-receipts/{goodsReceipt}/invoice', [SupplierInvoiceController::class, 'store'])->name('goods-receipts.invoice.store');
            Route::post('supplier-invoices/{supplierInvoice}/payment', [SupplierInvoiceController::class, 'recordPayment'])->name('supplier-invoices.payment');
        });
    });

// Modul Finance (Fase 3) — Chart of Accounts, mapping akun jurnal otomatis,
// & laporan keuangan. Jurnal sendiri di-post OTOMATIS lewat Observer (lihat
// App\Services\Finance\JournalPoster), tidak ada halaman input jurnal manual
// di sini — modul ini murni konfigurasi (COA baca-saja, mapping akun) +
// laporan.
Route::middleware(['auth', 'role:Admin,Finance'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function () {
        Route::middleware(['module:'.Module::FINANCE, 'module.maintenance:'.SystemModule::FINANCE_CHART_OF_ACCOUNTS])->group(function () {
            Route::get('chart-of-accounts', [ChartOfAccountController::class, 'index'])->name('chart-of-accounts.index');
        });

        Route::middleware(['module:'.Module::FINANCE, 'module.maintenance:'.SystemModule::FINANCE_MAPPINGS])->group(function () {
            Route::get('transaction-mappings', [TransactionAccountMappingController::class, 'index'])->name('transaction-mappings.index');
            Route::get('transaction-mappings/{transactionMapping}/edit', [TransactionAccountMappingController::class, 'edit'])->name('transaction-mappings.edit');
            Route::put('transaction-mappings/{transactionMapping}', [TransactionAccountMappingController::class, 'update'])->name('transaction-mappings.update');
        });

        Route::middleware(['module:'.Module::FINANCE, 'module.maintenance:'.SystemModule::FINANCE_REPORTS])->group(function () {
            Route::get('reports/general-ledger', [FinanceReportController::class, 'generalLedger'])->name('reports.general-ledger');
            Route::get('reports/expense-inventory', [FinanceReportController::class, 'expenseAndInventory'])->name('reports.expense-inventory');
            Route::get('reports/payables-aging', [FinanceReportController::class, 'payablesAging'])->name('reports.payables-aging');
            Route::get('reports/balance-sheet', [FinanceReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
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

        // Referensi visual & komponen inti Allez ERP (lihat .design-sync/NOTES.md).
        Route::get('design-system', [DesignSystemController::class, 'index'])->name('design-system.index');

        // Manajemen User — satu-satunya jalur bikin akun baru (/register
        // publik sudah dihapus, lihat routes/auth.php). Lihat
        // UserManagementController utk detail module_user vs module_role.
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::post('users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    });

require __DIR__.'/auth.php';
