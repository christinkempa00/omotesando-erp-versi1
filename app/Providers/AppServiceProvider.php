<?php

namespace App\Providers;

use App\Models\GA\GaRequest;
use App\Models\Purchasing\GoodsReceiptItem;
use App\Models\Purchasing\SupplierInvoice;
use App\Models\SCM\BatchLabel;
use App\Models\SCM\DeliveryNote;
use App\Models\SCM\DeliveryNoteItem;
use App\Models\SCM\DeliveryReceipt;
use App\Observers\BatchLabelObserver;
use App\Observers\DeliveryNoteItemObserver;
use App\Observers\DeliveryNoteObserver;
use App\Observers\DeliveryReceiptObserver;
use App\Observers\GaRequestObserver;
use App\Observers\GoodsReceiptItemObserver;
use App\Observers\SupplierInvoiceObserver;
use App\Support\RoleHomeResolver;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DeliveryNoteItem::observe(DeliveryNoteItemObserver::class);

        // Fase 1 — stok real-time (lihat App\Services\SCM\StockLedger).
        BatchLabel::observe(BatchLabelObserver::class);
        DeliveryNote::observe(DeliveryNoteObserver::class);
        DeliveryReceipt::observe(DeliveryReceiptObserver::class);

        // Fase 2 — Purchasing: goods_receipt_items ikut ledger yg sama,
        // tapi hanya utk PO kategori 'food' (lihat GoodsReceiptItemObserver).
        // Fase 3 — observer yg sama JUGA post jurnal Persediaan/Hutang Usaha
        // (bukan observer terpisah di GoodsReceipt — lihat docblock di
        // GoodsReceiptItemObserver utk alasan ordering-nya).
        GoodsReceiptItem::observe(GoodsReceiptItemObserver::class);

        // Fase 3 — Finance: jurnal otomatis (lihat App\Services\Finance\JournalPoster).
        GaRequest::observe(GaRequestObserver::class);
        SupplierInvoice::observe(SupplierInvoiceObserver::class);

        // Default bawaan Laravel selalu redirect ke route('dashboard') utk
        // user yang sudah login tapi mengunjungi /login lagi — tapi /dashboard
        // dibatasi role:GA,Admin,Finance, jadi role lain (IT/Head/SCM/
        // Purchasing) akan 403 begitu sampai. Pakai resolver yang sama dgn
        // RedirectsToRoleHome supaya selalu mendarat di halaman rumah yg benar.
        RedirectIfAuthenticated::redirectUsing(
            fn ($request) => $request->user()
                ? route(RoleHomeResolver::routeNameFor($request->user()))
                : route('login')
        );

        // Sama seperti fix split-hosting di bootstrap/app.php (usePublicPath)
        // — TAPI barryvdh/laravel-dompdf TIDAK memakai helper public_path()
        // Laravel, dia baca base_path('public') mentah-mentah lewat config
        // dompdf.public_path. Tanpa ini, Pdf::loadView() di manapun
        // (bukan cuma satu fitur — SEMUA export PDF di seluruh app) lempar
        // "RuntimeException: Cannot resolve public path" di hosting split
        // spt ini, krn <basePath>/public memang tidak pernah ada di sana.
        $splitPublicPath = base_path('../public_html');
        if (is_dir($splitPublicPath)) {
            config(['dompdf.public_path' => realpath($splitPublicPath)]);
        }
    }
}
