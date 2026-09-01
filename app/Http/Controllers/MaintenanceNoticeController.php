<?php

namespace App\Http\Controllers;

use App\Models\SystemModule;
use App\Support\RoleHomeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tujuan redirect CheckModuleMaintenance middleware — user (selain IT)
 * yang mencoba akses halaman yang sedang ditandai IT "Dalam Pemeliharaan"
 * diarahkan ke halaman "rumah" role-nya sendiri (lihat RoleHomeResolver —
 * BUKAN selalu '/dashboard', itu 403 utk role selain GA) dengan
 * notice di-flash ke session, lalu ditampilkan sebagai popup (lihat blok
 * "maintenanceNotice" di layouts/app.blade.php) — bukan halaman penuh
 * terpisah lagi (Revisi V1 10/08/2026).
 */
class MaintenanceNoticeController extends Controller
{
    /**
     * Peta "route rumah role" -> SystemModule key yang menggerbanginya —
     * dipakai utk hindari redirect-loop kalau modul yang sedang dalam
     * pemeliharaan justru halaman rumah role user itu sendiri. '/dashboard'
     * (GA) & 'it.modules.index' (IT selalu bypass middleware ini)
     * sengaja tidak masuk sini krn tidak digerbangi module.maintenance.
     */
    private const ROLE_HOME_MODULE_KEYS = [
        'head.dashboard' => SystemModule::HEAD_DASHBOARD,
    ];

    public function show(string $key, Request $request): RedirectResponse
    {
        $module = SystemModule::where('key', $key)->firstOrFail();

        $backRoute = RoleHomeResolver::routeNameFor($request->user());

        // Hindari redirect-loop: kalau modul yang lagi dalam pemeliharaan
        // justru halaman rumah role user itu sendiri, jangan diarahkan ke
        // situ lagi (bakal ke-gate lagi oleh middleware yang sama) — arahkan
        // ke profile (selalu bisa diakses semua role).
        if ((self::ROLE_HOME_MODULE_KEYS[$backRoute] ?? null) === $key) {
            $backRoute = 'profile.edit';
        }

        return redirect()->route($backRoute)->with('maintenanceNotice', [
            'name' => $module->name,
            'note' => $module->maintenance_note,
        ]);
    }
}
