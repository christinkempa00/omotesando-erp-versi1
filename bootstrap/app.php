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
        // CSRF token/session kadaluwarsa (419) — biasa terjadi kalau tab
        // dibiarkan terbuka lama lalu klik logout/submit form apa pun.
        // Bawaan Laravel cuma nampilin halaman error mati; redirect ke
        // login lebih masuk akal karena user secara efektif memang sudah
        // ter-logout. Dikecualikan utk request yang expectsJson() (mis.
        // fetch() Papan Kerja IT) supaya tetap dapat respons yang bisa
        // ditangani kodenya, bukan HTML halaman login.
        //
        // Type-hint HARUS HttpException (bukan TokenMismatchException) --
        // Handler::prepareException() sudah membungkus TokenMismatchException
        // jadi HttpException(419, ...) SEBELUM render callback manapun
        // dicek, jadi renderable(TokenMismatchException::class) tidak akan
        // pernah cocok.
        //
        // Pesannya lewat query string (?expired=1), BUKAN session()->flash()
        // -- exception yang dilempar sedalam ValidateCsrfToken bikin flash
        // ter-age dua kali sebelum request ini selesai (StartSession tidak
        // sempat nge-save normal krn exception melompati kode itu, tapi
        // ageFlashData() kedua tetap jalan entah dari mana -- sudah
        // ditelusuri baca source Laravel & dicoba save() manual, pesannya
        // tetap hilang sebelum halaman login sempat baca). Lihat
        // resources/views/auth/login.blade.php utk sisi baca query-nya.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419 && ! $request->expectsJson()) {
                // CSRF gagal berarti ValidateCsrfToken menolak request SEBELUM
                // sempat sampai ke LogoutController — user submit form logout
                // tapi TIDAK BENAR-BENAR ter-logout (masih authenticated).
                // Kalau cuma redirect ke /login di sini, RedirectIfAuthenticated
                // langsung mantulkan mereka balik ke dashboard krn masih login.
                // Karena submit ke /logout artinya niatnya sudah jelas (mau
                // keluar), logout-kan paksa di sini juga supaya redirect-nya
                // benar-benar mendarat di halaman login, bukan mantul balik.
                if ($request->routeIs('logout')) {
                    \Illuminate\Support\Facades\Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return redirect()->route('login', ['expired' => 1]);
            }
        });
    })
    ->create()
    ->usePublicPath(is_dir($splitPublicPath) ? $splitPublicPath : dirname(__DIR__).'/public');