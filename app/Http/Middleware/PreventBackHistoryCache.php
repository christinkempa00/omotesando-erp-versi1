<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PHP session.cache_limiter (nocache) cuma kirim "Cache-Control: no-cache,
 * private" — cukup utk cegah cache HTTP biasa, TAPI TIDAK cukup utk cegah
 * bfcache (back-forward cache) browser modern, yang butuh "no-store" secara
 * eksplisit. Tanpa ini: user logout lalu klik tombol Back browser bisa
 * melihat halaman terproteksi (mis. /dashboard) dari snapshot beku bfcache
 * TANPA request baru ke server sama sekali — auth check di server tidak
 * pernah jalan krn tidak ada request (ditemukan 24/08/2026).
 */
class PreventBackHistoryCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
