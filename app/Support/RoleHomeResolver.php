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
 * role:GA — 403 utk IT/Head).
 *
 * Role tanpa cabang di atas (belum ada role lain saat ini) jatuh ke
 * profile.edit (satu2nya halaman yg PASTI bisa diakses siapa pun yang
 * berhasil login, apa pun role yang dimiliki).
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

        if ($user->hasRole(Role::GA)) {
            return 'dashboard';
        }

        if ($user->hasRole(Role::OUTLET)) {
            return 'outlet.dashboard';
        }

        return 'profile.edit';
    }
}
