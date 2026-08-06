<?php

namespace Tests\Feature\Database;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Migrasi `2026_08_05_100002_create_module_user_table` sudah jalan lengkap
 * (termasuk backfill) saat RefreshDatabase menyiapkan skema test — tapi DB
 * test masih kosong waktu itu, jadi backfill-nya tidak ada apa-apa yang
 * dibackfill. Test ini mensimulasikan kondisi user "lama" (role_user sudah
 * ada SEBELUM fitur module_user dideploy) dengan memanggil ulang method
 * backfill-nya secara terisolasi (lihat refactor di migration file itu).
 */
class ModuleUserBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_populates_module_user_from_existing_role_assignments(): void
    {
        $role = Role::create(['name' => 'Legacy Role']);
        $moduleA = Module::create(['key' => 'legacy_a', 'label' => 'Legacy A', 'is_active' => true]);
        $moduleB = Module::create(['key' => 'legacy_b', 'label' => 'Legacy B', 'is_active' => true]);

        DB::table('module_role')->insert([
            ['role_id' => $role->id, 'module_id' => $moduleA->id],
            ['role_id' => $role->id, 'module_id' => $moduleB->id],
        ]);

        $user = User::factory()->create();
        DB::table('role_user')->insert(['role_id' => $role->id, 'user_id' => $user->id]);

        // Simulasikan kondisi sebelum backfill jalan (mis. baris ini akan
        // ada di deploy nyata sebelum migrasi ini dijalankan pertama kali).
        DB::table('module_user')->where('user_id', $user->id)->delete();
        $this->assertSame(0, DB::table('module_user')->where('user_id', $user->id)->count());

        $migration = require database_path('migrations/2026_08_05_100002_create_module_user_table.php');
        $migration->backfillFromRoleDefaults();

        $backfilled = DB::table('module_user')->where('user_id', $user->id)->pluck('module_id')->all();

        $this->assertEqualsCanonicalizing([$moduleA->id, $moduleB->id], $backfilled);
    }

    public function test_backfill_does_not_duplicate_existing_rows(): void
    {
        $role = Role::create(['name' => 'Legacy Role']);
        $module = Module::create(['key' => 'legacy_c', 'label' => 'Legacy C', 'is_active' => true]);

        DB::table('module_role')->insert(['role_id' => $role->id, 'module_id' => $module->id]);

        $user = User::factory()->create();
        DB::table('role_user')->insert(['role_id' => $role->id, 'user_id' => $user->id]);

        $migration = require database_path('migrations/2026_08_05_100002_create_module_user_table.php');
        $migration->backfillFromRoleDefaults();
        $migration->backfillFromRoleDefaults();

        $count = DB::table('module_user')
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->count();

        $this->assertSame(1, $count);
    }
}
