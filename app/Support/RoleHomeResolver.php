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
 * role:GA,Admin,Finance — 403 utk IT/Head/SCM/Purchasing).
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

        if ($user->hasRole(Role::PRODUKSI)) {
            return 'scm.materials.index';
        }

        if ($user->hasRole(Role::GUDANG, Role::OUTLET)) {
            return 'scm.deliveries.index';
        }

        if ($user->hasRole(Role::PURCHASING)) {
            return 'purchasing.purchase-requisitions.index';
        }

        return 'dashboard';
    }
}
