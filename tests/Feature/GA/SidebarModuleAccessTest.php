<?php

namespace Tests\Feature\GA;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario nyata Amanda (GA, cuma dicentang modul Inventaris Seragam):
 * sidebar sebelumnya cuma cek Module::is_active (status GLOBAL, diatur
 * Head), BUKAN akses per-user (module_user, diatur IT) — jadi dia tetap
 * lihat link Asset/Request/Work Log walau tidak dicentang IT sama sekali
 * (403 kalau diklik, tapi link-nya tetap tampil). Berlaku utk semua akun
 * GA yang dibuat IT ke depannya, bukan cuma Amanda.
 */
class SidebarModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function seedModules(): void
    {
        foreach ([Module::REQUESTS, Module::ASSETS, Module::UNIFORMS, Module::MAINTENANCE, Module::WORK_LOG] as $key) {
            Module::create(['key' => $key, 'label' => $key, 'is_active' => true]);
        }
    }

    public function test_sidebar_only_shows_modules_it_actually_granted_this_user(): void
    {
        $this->seedModules();

        $ga = User::factory()->create();
        $ga->roles()->attach(Role::firstOrCreate(['name' => Role::GA]));
        $ga->modules()->attach(Module::where('key', Module::UNIFORMS)->value('id'));

        $response = $this->actingAs($ga)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Uniform Inventory');
        $response->assertDontSee('Asset Inventory');
        $response->assertDontSee('Asset Request');
        $response->assertDontSee('Work Log');
    }

    public function test_admin_sees_all_modules_regardless_of_module_user_rows(): void
    {
        $this->seedModules();

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::firstOrCreate(['name' => Role::ADMIN]));
        // Sengaja TIDAK attach modules apa pun — Admin harus tetap lolos.

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Uniform Inventory');
        $response->assertSee('Asset Inventory');
        $response->assertSee('Asset Request');
        $response->assertSee('Work Log');
    }
}
