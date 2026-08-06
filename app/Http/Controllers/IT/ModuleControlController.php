<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kontrol Akses & Mode Pemeliharaan — khusus role IT. Menandai
 * halaman/section GA & Head sebagai "Dalam Pemeliharaan" (dibaca oleh
 * CheckModuleMaintenance middleware di routes/web.php). Mengikuti pola yang
 * sama dengan HeadModuleController (aktif/nonaktifkan modul oleh Head):
 * simple index+toggle, dicatat ke activity_logs yang sama.
 */
class ModuleControlController extends Controller
{
    public function index(): View
    {
        $modules = SystemModule::with(['updatedBy', 'linkedTasks.assignee:id,name', 'linkedTasks.column'])->orderBy('name')->get();

        $recentActivity = ActivityLog::with('user')
            ->whereIn('action', ['system_module.maintenance_on', 'system_module.maintenance_off'])
            ->latest()
            ->limit(15)
            ->get();

        return view('it.modules.index', [
            'modules' => $modules,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Toggle via AJAX (fetch), bukan submit form biasa — supaya switch bisa
     * langsung update tanpa reload halaman penuh, sesuai permintaan.
     */
    public function toggle(Request $request, SystemModule $systemModule): JsonResponse
    {
        $validated = $request->validate([
            'is_under_maintenance' => ['required', 'boolean'],
            'maintenance_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $turningOn = $validated['is_under_maintenance'];

        if ($turningOn && trim((string) ($validated['maintenance_note'] ?? '')) === '') {
            return response()->json([
                'message' => 'Catatan wajib diisi saat menandai modul dalam pemeliharaan.',
            ], 422);
        }

        $systemModule->is_under_maintenance = $turningOn;
        $systemModule->maintenance_note = $turningOn ? $validated['maintenance_note'] : null;
        $systemModule->updated_by = $request->user()->id;
        $systemModule->save();

        $action = $turningOn ? 'system_module.maintenance_on' : 'system_module.maintenance_off';
        $description = ($turningOn ? 'Menandai' : 'Mengembalikan')." {$systemModule->name} ".($turningOn ? 'dalam pemeliharaan' : 'ke status normal');

        ActivityLog::record($request->user(), $action, $systemModule, $description, [
            'maintenance_note' => $systemModule->maintenance_note,
        ]);

        return response()->json([
            'message' => $description.' berhasil.',
            'module' => [
                'id' => $systemModule->id,
                'is_under_maintenance' => $systemModule->is_under_maintenance,
                'maintenance_note' => $systemModule->maintenance_note,
                'updated_by' => $systemModule->updatedBy?->name,
                'updated_at' => $systemModule->updated_at->format('d M Y H:i'),
            ],
        ]);
    }
}
