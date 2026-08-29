<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard khusus grup route outlet.* — controller yang dipakai bersama GA
 * (lihat routes/web.php) cuma scoping query ke branch KALAU user->branch
 * ada (no-op utk staff GA/HQ yang memang tidak punya branch_id). Tanpa
 * guard ini, akun Outlet yang lupa di-set branch_id oleh IT akan lolos
 * middleware role:Outlet lalu diam-diam melihat data SEMUA outlet (branch
 * scoping-nya jadi no-op juga) — bukan 403, bukan kosong, tapi bocor.
 */
class EnsureOutletHasBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->branch) {
            abort(403, 'Akun Anda belum terhubung ke outlet manapun. Hubungi IT untuk mengatur ini.');
        }

        return $next($request);
    }
}
