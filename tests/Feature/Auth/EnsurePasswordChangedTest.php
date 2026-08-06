<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnsurePasswordChangedTest extends TestCase
{
    use RefreshDatabase;

    private function makeForcedUser(): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('temporary-password'),
            'password_must_change' => true,
        ]);
        $user->roles()->attach(Role::create(['name' => Role::GA]));

        return $user;
    }

    public function test_forced_user_is_redirected_away_from_other_pages(): void
    {
        $user = $this->makeForcedUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('password.force-change'));
    }

    public function test_forced_user_can_still_reach_force_change_page(): void
    {
        $user = $this->makeForcedUser();

        $this->actingAs($user)
            ->get(route('password.force-change'))
            ->assertOk();
    }

    public function test_forced_user_can_still_logout(): void
    {
        $user = $this->makeForcedUser();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');
    }

    public function test_forced_user_can_change_password_without_current_password(): void
    {
        $user = $this->makeForcedUser();

        $response = $this->actingAs($user)->put('/password', [
            'password' => 'brand-new-password-1',
            'password_confirmation' => 'brand-new-password-1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertFalse($user->password_must_change);
        $this->assertTrue(Hash::check('brand-new-password-1', $user->password));
    }

    public function test_forced_user_no_longer_redirected_after_password_changed(): void
    {
        $user = $this->makeForcedUser();

        $this->actingAs($user)->put('/password', [
            'password' => 'brand-new-password-1',
            'password_confirmation' => 'brand-new-password-1',
        ]);

        $this->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertOk();
    }
}
