<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penggunaan di route:
 *   Route::get('/ga/requests', ...)->middleware('role:GA,Head');
 *
 * User lolos kalau punya SALAH SATU dari role yang disebut (OR, bukan AND),
 * karena satu user bisa punya lebih dari satu role.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user->hasRole(...$roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
