<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\SystemModule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penggunaan di route:
 *   Route::middleware('module.maintenance:ga.requests')->group(...);
 *
 * Beda dengan ModuleAccessMiddleware (aktif/nonaktif kasar per modul,
 * dikontrol Head), middleware ini per-halaman & dikontrol role IT — kalau
 * is_under_maintenance, user selain IT diarahkan ke halaman "Sedang
 * Dalam Pemeliharaan" (bukan abort/403 biasa). IT tetap lolos supaya
 * bisa terus mengerjakan perbaikan, dengan banner kecil sebagai penanda.
 */
class CheckModuleMaintenance
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        $module = SystemModule::where('key', $key)->first();

        if (! $module || ! $module->is_under_maintenance) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->hasRole(Role::IT)) {
            // Dibagikan ke semua view di request ini supaya layout bisa
            // menampilkan banner "modul ini dalam mode pemeliharaan utk role
            // lain" tanpa tiap controller perlu tahu soal ini.
            view()->share('maintenanceBannerModule', $module);

            return $next($request);
        }

        return redirect()->route('maintenance.notice', ['key' => $key]);
    }
}
