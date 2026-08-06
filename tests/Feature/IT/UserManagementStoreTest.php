<?php

namespace Tests\Feature\IT;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
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

        $gudangRole = Role::create(['name' => Role::GUDANG]);

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
}
