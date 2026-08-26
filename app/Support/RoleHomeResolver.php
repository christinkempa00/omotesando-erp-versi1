<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

/**
 * Nama route "rumah" per role — satu sumber kebenaran dipakai oleh
 * RedirectsToRoleHome (setelah login/ganti-password wajib) DAN oleh
 * override RedirectIfAuthenticated (lihat AppServiceProvider) supaya
 * user yang sudah login dan mengunjungi /login lagi diarahkan ke
 * halamannya sendiri, bukan selalu ke /dashboard (yang dibatasi
 * role:GA,Admin — 403 utk IT/Head).
 *
 * Outlet belum punya halaman "rumah" sendiri (Monitoring Outlet — Fase B-2,
 * belum dibangun), jadi jatuh ke profile.edit (satu2nya halaman yg PASTI
 * bisa diakses siapa pun yang berhasil login, apa pun role yang dimiliki).
 */
class RoleHomeResolver
{
    public static function routeNameFor(User $user): string
    {
        if ($user->hasRole(Role::HEAD)) {
            return 'head.dashboard';
        }

        if ($user->hasRole(Role::IT)) {
            return 'it.modules.index';
        }

        if ($user->hasRole(Role::GA, Role::ADMIN)) {
            return 'dashboard';
        }

        return 'profile.edit';
    }
}
