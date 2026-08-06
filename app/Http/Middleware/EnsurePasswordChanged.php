<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * User baru (dibuat IT) atau yang di-reset password-nya WAJIB ganti
 * password sendiri sebelum bisa akses halaman lain mana pun — dipasang
 * global di grup middleware 'web' (lihat bootstrap/app.php), pola sama
 * seperti Illuminate\Auth\Middleware\EnsureEmailIsVerified bawaan Laravel
 * (guard-by-flag, redirect ke satu halaman kalau kondisinya belum terpenuhi).
 *
 * Flag password_must_change jadi false lagi otomatis begitu password
 * berhasil diganti — lihat App\Http\Controllers\Auth\PasswordController::update().
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->password_must_change) {
            return $next($request);
        }

        // Jangan redirect kalau sudah di halaman ganti password itu sendiri
        // (atau endpoint submit-nya/logout) — supaya tidak infinite redirect.
        if ($request->routeIs('password.force-change', 'password.update', 'logout')) {
            return $next($request);
        }

        return redirect()->route('password.force-change');
    }
}
