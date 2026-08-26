<?php

use App\Http\Middleware\CheckModuleMaintenance;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\ModuleAccessMiddleware;
use App\Http\Middleware\PreventBackHistoryCache;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Sebagian shared hosting (mis. struktur split Hostinger: public_html
// terpisah dari laravel_app, index.php di public_html di-patch nunjuk ke
// ../laravel_app) TIDAK punya folder <app>/public sama sekali — asset
// hasil build (termasuk build/manifest.json punya Vite) sebenarnya ada di
// public_html, sibling dari basePath, bukan di dalamnya. Laravel secara
// default selalu cari public_path() = <basePath>/public, jadi tanpa ini
// Vite manifest "tidak ketemu" & tiap halaman yang pakai @vite(...) 500.
// Deteksi otomatis via keberadaan folder, BUKAN .env — supaya dev lokal
// (yang tidak punya sibling public_html) tetap otomatis pakai public/
// biasa tanpa perlu konfigurasi apa pun.
$splitPublicPath = dirname(__DIR__, 2).'/public_html';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // routes/deploy.php didaftarkan lewat then: (bukan web:) supaya
        // TIDAK ikut middleware group 'web' — lihat komentar di file itu.
        then: function () {
            require __DIR__.'/../routes/deploy.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'module' => ModuleAccessMiddleware::class,
            'module.maintenance' => CheckModuleMaintenance::class,
        ]);

        // Global di semua route 'web' — user yang wajib ganti password
        // (akun baru/reset dari IT) di-redirect paksa ke halaman ganti
        // password dulu, apa pun halaman yang mereka coba akses.
        $middleware->appendToGroup('web', EnsurePasswordChanged::class);

        // Global di semua route 'web' — paksa "no-store" di tiap respons
        // supaya browser tidak bisa tampilkan halaman dari bfcache setelah
        // logout (lihat docblock PreventBackHistoryCache).
        $middleware->appendToGroup('web', PreventBackHistoryCache::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create()
    ->usePublicPath(is_dir($splitPublicPath) ? $splitPublicPath : dirname(__DIR__).'/public');