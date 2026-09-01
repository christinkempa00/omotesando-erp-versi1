<?php

use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Papan Kerja, Kontrol Modul, dan Manajemen User selama ini cuma dijaga
 * `role:IT` (lihat routes/web.php) — belum ikut sistem module_user/
 * ModuleAccessMiddleware yang sudah lama dipakai modul GA/Head/Outlet.
 * Migrasi ini menyamakan perlakuannya: 3 modul baru + module_role (saran
 * default form Manajemen User saat role IT dicentang) + backfill WAJIB ke
 * module_user semua akun ber-role IT saat ini, supaya tidak ada staff IT
 * aktif yang ke-403 pas deploy (pola sama persis dgn
 * 2026_08_05_100002_create_module_user_table::backfillFromRoleDefaults()).
 */
return new class extends Migration
{
    private const MODULES = [
        ['key' => Module::IT_BOARD, 'label' => 'Papan Kerja', 'description' => 'Board Kanban tugas & bug fix IT'],
        ['key' => Module::IT_MODULE_CONTROL, 'label' => 'Kontrol Modul', 'description' => 'Aktif/nonaktifkan mode pemeliharaan per halaman'],
        ['key' => Module::IT_USER_MANAGEMENT, 'label' => 'Manajemen User', 'description' => 'Buat & atur akun, role, akses modul, & tier per halaman'],
    ];

    public function up(): void
    {
        $itRoleId = Role::where('name', Role::IT)->value('id');

        $moduleIds = [];
        foreach (self::MODULES as $data) {
            $module = Module::updateOrCreate(['key' => $data['key']], $data + ['is_active' => true]);
            $moduleIds[] = $module->id;

            if ($itRoleId) {
                $module->roles()->syncWithoutDetaching([$itRoleId]);
            }
        }

        if ($itRoleId && $moduleIds !== []) {
            $this->backfillItUsers($itRoleId, $moduleIds);
        }
    }

    /**
     * @param  array<int, int>  $moduleIds
     */
    private function backfillItUsers(int $itRoleId, array $moduleIds): void
    {
        $userIds = DB::table('role_user')->where('role_id', $itRoleId)->pluck('user_id');

        if ($userIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];
        foreach ($userIds as $userId) {
            foreach ($moduleIds as $moduleId) {
                $rows[] = [
                    'user_id' => $userId,
                    'module_id' => $moduleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('module_user')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        // module_role & module_user ikut terhapus (cascadeOnDelete).
        Module::whereIn('key', array_column(self::MODULES, 'key'))->delete();
    }
};
