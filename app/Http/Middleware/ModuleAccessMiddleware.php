<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penggunaan di route:
 *   Route::middleware('module:assets')->group(...);
 *
 * Beda dengan RoleMiddleware (cek role secara statis dari kode), middleware
 * ini baca dari tabel modules (aktif/nonaktif, diubah Head lewat Kontrol
 * Modul) dan module_user (akses PER USER, diubah IT lewat Manajemen User)
 * — tanpa perlu deploy ulang.
 */
class ModuleAccessMiddleware
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        $module = Module::where('key', $key)->first();

        if (! $module || ! $module->is_active) {
            abort(503, 'Modul ini sedang dinonaktifkan oleh Head.');
        }

        $user = $request->user();

        // Admin selalu bisa akses semua modul — supaya tidak ada skenario
        // Head tidak sengaja mengunci akses administratifnya sendiri.
        if (! $user || $user->hasRole(Role::ADMIN)) {
            return $next($request);
        }

        // Akses modul per USER (module_user), BUKAN lagi langsung dari
        // role — lihat User::modules(). module_role (roles() di atas)
        // tetap ada, tapi cuma dipakai sbg saran default saat IT membuat
        // akun baru (lihat UserManagementController), bukan yang
        // menentukan akses real-time lagi.
        $hasAccess = $user->modules()->where('modules.id', $module->id)->exists();

        if (! $hasAccess) {
            abort(403, 'Akun Anda tidak memiliki akses ke modul ini. Hubungi IT.');
        }

        return $next($request);
    }
}
