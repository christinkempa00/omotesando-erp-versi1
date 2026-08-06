<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kontrol Modul — Head bisa aktif/nonaktifkan tiap modul GA (dibaca oleh
 * ModuleAccessMiddleware di routes/web.php). Semua perubahan dicatat ke
 * activity_logs.
 *
 * Catatan: fitur "atur role akses per modul" sengaja TIDAK ada di sini —
 * itu direncanakan pindah ke modul role IT terpisah nanti. Tabel
 * module_role & pengecekan role di ModuleAccessMiddleware tetap ada
 * (dipertahankan dengan role default hasil seeding), hanya UI untuk
 * mengubahnya yang dikeluarkan dari modul Head.
 */
class HeadModuleController extends Controller
{
    public function index(): View
    {
        $modules = Module::orderBy('label')->get();

        $recentActivity = ActivityLog::with('user')
            ->whereIn('action', ['module.enabled', 'module.disabled'])
            ->latest()
            ->limit(15)
            ->get();

        return view('head.modules.index', [
            'modules' => $modules,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function toggle(Request $request, Module $module): RedirectResponse
    {
        $module->is_active = ! $module->is_active;
        $module->save();

        $action = $module->is_active ? 'module.enabled' : 'module.disabled';
        $description = ($module->is_active ? 'Mengaktifkan' : 'Menonaktifkan')." modul {$module->label}";

        ActivityLog::record($request->user(), $action, $module, $description);

        return back()->with('success', $description.' berhasil.');
    }
}
