<?php

namespace Tests\Feature\IT;

use App\Models\Branch;
use App\Models\Division;
use App\Models\IT\ItBoard;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Migrasi 2026_09_01_000001_add_it_modules_and_backfill_access menggerbang
 * Papan Kerja/Kontrol Modul/Manajemen User di belakang module_user (sama
 * spt modul GA), plus guard self-lockout & endpoint quick-create Divisi/
 * Branch yang menyertainya. Lihat pola test migrasi serupa di
 * tests/Feature/Database/ModuleUserBackfillTest.php.
 */
class ItModuleGatingTest extends TestCase
{
    use RefreshDatabase;

    private function itModule(string $key, string $label): Module
    {
        return Module::firstOrCreate(['key' => $key], ['label' => $label, 'is_active' => true]);
    }

    public function test_backfill_grants_new_it_modules_to_existing_it_users(): void
    {
        $role = Role::create(['name' => Role::IT]);
        $user = User::factory()->create();
        DB::table('role_user')->insert(['role_id' => $role->id, 'user_id' => $user->id]);

        $migration = require database_path('migrations/2026_09_01_000001_add_it_modules_and_backfill_access.php');
        $migration->up();

        $moduleKeys = $user->modules()->pluck('modules.key')->all();

        $this->assertEqualsCanonicalizing(
            [Module::IT_BOARD, Module::IT_MODULE_CONTROL, Module::IT_USER_MANAGEMENT],
            $moduleKeys
        );
    }

    public function test_it_user_without_board_module_gets_403_on_board(): void
    {
        $it = User::factory()->create();
        $it->roles()->attach(Role::create(['name' => Role::IT]));
        // Sengaja TIDAK dikasih modul Papan Kerja.

        $this->actingAs($it)->get('/it/board')->assertForbidden();
    }

    public function test_it_user_with_board_module_can_access_board(): void
    {
        ItBoard::create(['name' => 'Pengembangan Sistem ERP']);

        $it = User::factory()->create();
        $it->roles()->attach(Role::create(['name' => Role::IT]));
        $it->modules()->attach($this->itModule(Module::IT_BOARD, 'Papan Kerja'));

        $this->actingAs($it)->get('/it/board')->assertOk();
    }

    public function test_self_lockout_guard_keeps_user_management_module_on_own_account(): void
    {
        $itRole = Role::create(['name' => Role::IT]);
        $userManagementModule = $this->itModule(Module::IT_USER_MANAGEMENT, 'Manajemen User');

        $it = User::factory()->create();
        $it->roles()->attach($itRole);
        $it->modules()->attach($userManagementModule);

        // Submit form edit akun SENDIRI tanpa mencentang Manajemen User sama
        // sekali (array modules kosong) — guard harus tetap memaksa modul
        // ini nempel supaya IT ini tidak lock out dari halaman ini.
        $response = $this->actingAs($it)->put("/it/users/{$it->id}", [
            'name' => $it->name,
            'email' => $it->email,
            'division_id' => null,
            'branch_id' => null,
            'roles' => [$itRole->id],
            'modules' => [],
        ]);

        $response->assertRedirect();
        $this->assertTrue($it->fresh()->modules()->where('modules.id', $userManagementModule->id)->exists());
    }

    public function test_module_checklist_is_fully_respected_for_other_accounts(): void
    {
        $itRole = Role::create(['name' => Role::IT]);
        $userManagementModule = $this->itModule(Module::IT_USER_MANAGEMENT, 'Manajemen User');

        $actingIt = User::factory()->create();
        $actingIt->roles()->attach($itRole);
        $actingIt->modules()->attach($userManagementModule);

        $otherIt = User::factory()->create();
        $otherIt->roles()->attach($itRole);
        $otherIt->modules()->attach($userManagementModule);

        // Guard self-lockout HANYA berlaku utk akun sendiri — mencabut modul
        // Manajemen User dari akun ORANG LAIN harus benar-benar tercabut.
        $response = $this->actingAs($actingIt)->put("/it/users/{$otherIt->id}", [
            'name' => $otherIt->name,
            'email' => $otherIt->email,
            'division_id' => null,
            'branch_id' => null,
            'roles' => [$itRole->id],
            'modules' => [],
        ]);

        $response->assertRedirect();
        $this->assertFalse($otherIt->fresh()->modules()->where('modules.id', $userManagementModule->id)->exists());
    }

    public function test_it_can_quick_create_a_division(): void
    {
        $it = User::factory()->create();
        $it->roles()->attach(Role::create(['name' => Role::IT]));
        $it->modules()->attach($this->itModule(Module::IT_USER_MANAGEMENT, 'Manajemen User'));

        $response = $this->actingAs($it)->postJson('/it/divisions', ['name' => 'Marketing']);

        $response->assertCreated()->assertJsonStructure(['id', 'name']);
        $this->assertDatabaseHas('divisions', ['name' => 'Marketing']);
    }

    public function test_it_can_quick_create_a_branch(): void
    {
        $it = User::factory()->create();
        $it->roles()->attach(Role::create(['name' => Role::IT]));
        $it->modules()->attach($this->itModule(Module::IT_USER_MANAGEMENT, 'Manajemen User'));

        $response = $this->actingAs($it)->postJson('/it/branches', ['name' => 'Outlet Baru']);

        $response->assertCreated()->assertJsonStructure(['id', 'name']);
        $this->assertDatabaseHas('branches', ['name' => 'Outlet Baru', 'is_active' => true]);
    }

    public function test_quick_create_division_rejects_duplicate_name(): void
    {
        Division::create(['name' => 'General Affair', 'code' => 'GA']);

        $it = User::factory()->create();
        $it->roles()->attach(Role::create(['name' => Role::IT]));
        $it->modules()->attach($this->itModule(Module::IT_USER_MANAGEMENT, 'Manajemen User'));

        $response = $this->actingAs($it)->postJson('/it/divisions', ['name' => 'General Affair']);

        $response->assertStatus(422);
    }

    public function test_quick_created_branch_gets_a_unique_generated_code(): void
    {
        // "Zodiac Baru" -> base code generator akan hasilkan "ZODIACBARU"
        // (10 char pertama alfanumerik) — pre-create baris dgn code itu
        // persis supaya generator kepaksa lewat jalur suffix-nya.
        Branch::create(['name' => 'Existing Branch', 'code' => 'ZODIACBARU', 'is_active' => true]);

        $it = User::factory()->create();
        $it->roles()->attach(Role::create(['name' => Role::IT]));
        $it->modules()->attach($this->itModule(Module::IT_USER_MANAGEMENT, 'Manajemen User'));

        $response = $this->actingAs($it)->postJson('/it/branches', ['name' => 'Zodiac Baru']);

        $response->assertCreated();
        $branch = Branch::where('name', 'Zodiac Baru')->firstOrFail();
        $this->assertSame('ZODIACBARU1', $branch->code);
    }
}
