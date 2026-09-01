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
            return self::gaHomeRoute($user);
        }

        if ($user->hasRole(Role::OUTLET)) {
            return 'outlet.dashboard';
        }

        return 'profile.edit';
    }

    /**
     * 'dashboard' sekarang digerbang module:dashboard (lihat routes/web.php)
     * — akun GA yang cuma dikasih 1 modul spesifik (mis. Amanda, cuma
     * Inventaris Seragam, tidak diberi Dashboard) harus mendarat langsung
     * ke modul itu, bukan ke 'dashboard' yang akan 403 utk dia. Urutan
     * prioritas sama seperti urutan menu di sidebar GA.
     */
    private static function gaHomeRoute(User $user): string
    {
        $moduleKeys = $user->modules()->pluck('modules.key')->all();

        if (in_array(Module::DASHBOARD, $moduleKeys, true)) {
            return 'dashboard';
        }

        $priority = [
            Module::UNIFORMS => 'ga.uniforms.records.index',
            Module::ASSETS => 'ga.assets.index',
            Module::REQUESTS => 'ga.requests.index',
            Module::MAINTENANCE => 'ga.maintenance.index',
            Module::WORK_LOG => 'ga.worklogs.index',
        ];

        foreach ($priority as $key => $route) {
            if (in_array($key, $moduleKeys, true)) {
                return $route;
            }
        }

        return 'profile.edit';
    }
}
