<?php

namespace Tests\Feature\IT;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::create(['name' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_it_role_can_access_user_management_index(): void
    {
        $it = $this->makeUserWithRole(Role::IT);

        $this->actingAs($it)
            ->get('/it/users')
            ->assertOk();
    }

    public function test_it_role_can_access_create_form(): void
    {
        $it = $this->makeUserWithRole(Role::IT);

        $this->actingAs($it)
            ->get('/it/users/create')
            ->assertOk();
    }

    public function test_non_it_role_is_forbidden_from_user_management(): void
    {
        $ga = $this->makeUserWithRole(Role::GA);

        $this->actingAs($ga)
            ->get('/it/users')
            ->assertForbidden();

        $this->actingAs($ga)
            ->get('/it/users/create')
            ->assertForbidden();
    }
}
