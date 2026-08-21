<?php

namespace App\Support;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;

/**
 * Nama route "rumah" per role — satu sumber kebenaran dipakai oleh
 * RedirectsToRoleHome (setelah login/ganti-password wajib) DAN oleh
 * override RedirectIfAuthenticated (lihat AppServiceProvider) supaya
 * user yang sudah login dan mengunjungi /login lagi diarahkan ke
 * halamannya sendiri, bukan selalu ke /dashboard (yang dibatasi
 * role:GA,Admin,Finance — 403 utk IT/Head/SCM/Purchasing).
 *
 * Beberapa halaman "rumah" (SCM/Purchasing) digerbang ganda: role (grup
 * route) DAN modul per-user (module_user, diatur IT lewat Manajemen User —
 * lihat ModuleAccessMiddleware). Role cocok TIDAK menjamin modul-nya
 * diberikan — mis. akun Outlet yang sengaja cuma dikasih akses "Monitoring
 * Outlet", bukan modul default role-nya (SCM Deliveries). Kalau langsung
 * return nama route tanpa cek modul, user begini akan 403 begitu sampai
 * (dialami langsung 21/08/2026). Makanya tiap kandidat yg digerbang modul
 * dicek dulu lewat hasModuleAccess() sebelum dipilih; kalau gagal, lanjut
 * ke kandidat berikutnya, dan akhirnya jatuh ke profile.edit (satu2nya
 * halaman yg PASTI bisa diakses siapa pun yang berhasil login, apa pun
 * role/modul yang dimiliki).
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

        if ($user->hasRole(Role::PRODUKSI) && static::hasModuleAccess($user, Module::SCM_MATERIALS)) {
            return 'scm.materials.index';
        }

        if ($user->hasRole(Role::GUDANG, Role::OUTLET) && static::hasModuleAccess($user, Module::SCM_DELIVERIES)) {
            return 'scm.deliveries.index';
        }

        if ($user->hasRole(Role::PURCHASING) && static::hasModuleAccess($user, Module::PURCHASING_REQUISITIONS)) {
            return 'purchasing.purchase-requisitions.index';
        }

        if ($user->hasRole(Role::GA, Role::ADMIN, Role::FINANCE)) {
            return 'dashboard';
        }

        return 'profile.edit';
    }

    private static function hasModuleAccess(User $user, string $moduleKey): bool
    {
        // Sama dgn pengecualian di ModuleAccessMiddleware — Admin selalu
        // lolos semua modul, jadi tidak perlu dicek ke module_user.
        if ($user->hasRole(Role::ADMIN)) {
            return true;
        }

        return $user->modules()->where('modules.key', $moduleKey)->exists();
    }
}
