<?php

namespace Tests\Feature\IT;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPagePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserManagementStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_gets_final_checklist_modules_not_role_defaults(): void
    {
        $it = User::factory()->create();
        $it->roles()->attach(Role::create(['name' => Role::IT]));
        $it->modules()->attach(Module::firstOrCreate(['key' => Module::IT_USER_MANAGEMENT], ['label' => 'Manajemen User', 'is_active' => true]));

        $gudangRole = Role::create(['name' => 'Gudang']);

        $defaultModule = Module::create(['key' => 'default_module', 'label' => 'Default Module', 'is_active' => true]);
        $chosenModuleA = Module::create(['key' => 'chosen_a', 'label' => 'Chosen A', 'is_active' => true]);
        $chosenModuleB = Module::create(['key' => 'chosen_b', 'label' => 'Chosen B', 'is_active' => true]);

        // Default modul role Gudang (module_role) SENGAJA beda dari yang akan
        // dikirim di form — supaya kebukti checklist final-lah yang disimpan.
        DB::table('module_role')->insert([
            'role_id' => $gudangRole->id,
            'module_id' => $defaultModule->id,
        ]);

        $response = $this->actingAs($it)->post('/it/users', [
            'name' => 'New Gudang User',
            'email' => 'new.gudang@omotesando.test',
            'password' => 'a-strong-password-123',
            'division_id' => null,
            'branch_id' => null,
            'roles' => [$gudangRole->id],
            'modules' => [$chosenModuleA->id, $chosenModuleB->id],
        ]);

        $response->assertRedirect('/it/users');

        $newUser = User::where('email', 'new.gudang@omotesando.test')->firstOrFail();

        $this->assertTrue($newUser->password_must_change);
        $this->assertTrue($newUser->is_active);
        $this->assertEqualsCanonicalizing([$gudangRole->id], $newUser->roles()->pluck('roles.id')->all());

        $moduleIds = $newUser->modules()->pluck('modules.id')->all();
        $this->assertEqualsCanonicalizing([$chosenModuleA->id, $chosenModuleB->id], $moduleIds);
        $this->assertNotContains($defaultModule->id, $moduleIds);
    }

    public function test_page_access_tiers_are_saved_per_page_key(): void
    {
        $it = User::factory()->create();
        $it->roles()->attach(Role::create(['name' => Role::IT]));
        $it->modules()->attach(Module::firstOrCreate(['key' => Module::IT_USER_MANAGEMENT], ['label' => 'Manajemen User', 'is_active' => true]));

        $outletRole = Role::create(['name' => Role::OUTLET]);
        $requestsModule = Module::create(['key' => Module::REQUESTS, 'label' => 'Pengajuan', 'is_active' => true]);
        $uniformsModule = Module::create(['key' => Module::UNIFORMS, 'label' => 'Seragam', 'is_active' => true]);

        $response = $this->actingAs($it)->post('/it/users', [
            'name' => 'New Outlet User',
            'email' => 'new.outlet@omotesando.test',
            'password' => 'a-strong-password-123',
            'division_id' => null,
            'branch_id' => null,
            'roles' => [$outletRole->id],
            'modules' => [$requestsModule->id, $uniformsModule->id],
            'page_access' => [
                UserPagePermission::PAGE_REQUESTS => UserPagePermission::ACCESS_VIEW,
                UserPagePermission::PAGE_UNIFORMS_STOCKS => UserPagePermission::ACCESS_VIEW,
                UserPagePermission::PAGE_UNIFORMS_RECORDS => UserPagePermission::ACCESS_EDIT,
            ],
        ]);

        $response->assertRedirect('/it/users');

        $newUser = User::where('email', 'new.outlet@omotesando.test')->firstOrFail();
        $levels = $newUser->pagePermissions()->pluck('access_level', 'page_key')->all();

        $this->assertSame(UserPagePermission::ACCESS_VIEW, $levels[UserPagePermission::PAGE_REQUESTS]);
        $this->assertSame(UserPagePermission::ACCESS_VIEW, $levels[UserPagePermission::PAGE_UNIFORMS_STOCKS]);
        $this->assertSame(UserPagePermission::ACCESS_EDIT, $levels[UserPagePermission::PAGE_UNIFORMS_RECORDS]);
    }
}
