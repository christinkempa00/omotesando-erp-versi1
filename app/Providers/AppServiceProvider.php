<?php

namespace App\Providers;

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
        // Semua observer SCM/Purchasing/GaRequest (ledger stok, jurnal
        // otomatis) dihapus 24/08/2026 bersamaan dengan modul-modul itu —
        // lihat README "Riwayat Perubahan" tanggal yang sama.

        // Default bawaan Laravel selalu redirect ke route('dashboard') utk
        // user yang sudah login tapi mengunjungi /login lagi — tapi /dashboard
        // dibatasi role:GA, jadi role lain (IT/Head/Outlet) akan 403
        // begitu sampai. Pakai resolver yang sama dgn RedirectsToRoleHome
        // supaya selalu mendarat di halaman rumah yg benar.
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
