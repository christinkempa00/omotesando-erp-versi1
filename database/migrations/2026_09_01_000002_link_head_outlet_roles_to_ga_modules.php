<?php

use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * module_role selama ini cuma dipakai sbg SARAN default checklist Akses
 * Modul saat IT bikin akun baru — ModuleSeeder dulu cuma menghubungkan
 * GA & Admin ke 5 modul GA (requests/assets/uniforms/maintenance/work_log),
 * padahal Head & Outlet SAMA-SAMA butuh modul ini juga (Head monitoring
 * lewat head.*, Outlet lewat outlet.* — keduanya digerbang `module:` persis
 * spt ga.*, lihat routes/web.php). Ketidaklengkapan ini baru kelihatan
 * sekarang krn form Manajemen User mulai memfilter tampilan Akses Modul
 * berdasarkan role yang dicentang (lihat _access-fields.blade.php) — tanpa
 * migrasi ini, akun Head/Outlet yang sudah py modul-modul itu tercentang
 * (module_user) akan tampak "hilang" dari daftar begitu form difilter.
 * TIDAK menyentuh module_user user manapun — cuma melengkapi peta saran.
 */
return new class extends Migration
{
    private const GA_MODULE_KEYS = [
        Module::REQUESTS,
        Module::ASSETS,
        Module::UNIFORMS,
        Module::MAINTENANCE,
        Module::WORK_LOG,
    ];

    public function up(): void
    {
        $moduleIds = Module::whereIn('key', self::GA_MODULE_KEYS)->pluck('id');
        $roleIds = Role::whereIn('name', [Role::HEAD, Role::OUTLET])->pluck('id');

        $now = now();
        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($moduleIds as $moduleId) {
                $rows[] = ['role_id' => $roleId, 'module_id' => $moduleId, 'created_at' => $now, 'updated_at' => $now];
            }
        }

        if ($rows !== []) {
            DB::table('module_role')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        $moduleIds = Module::whereIn('key', self::GA_MODULE_KEYS)->pluck('id');
        $roleIds = Role::whereIn('name', [Role::HEAD, Role::OUTLET])->pluck('id');

        DB::table('module_role')
            ->whereIn('role_id', $roleIds)
            ->whereIn('module_id', $moduleIds)
            ->delete();
    }
};
