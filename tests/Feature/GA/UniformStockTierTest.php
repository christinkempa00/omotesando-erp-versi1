<?php

namespace Tests\Feature\GA;

use App\Models\Branch;
use App\Models\GA\UniformStock;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPagePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario nyata Amanda (GA): tier "Lihat saja" utk Seragam-Stok harus
 * menyembunyikan tombol Buat Varian/Edit/Hapus/Restock di view ga.* (bukan
 * cuma memblokir submit-nya di controller) — dan tier "Bisa edit" tetap
 * menampilkannya seperti biasa. Beda dari OutletAccessLevelTest yang
 * menguji controller/branch-scope; ini menguji lapisan view GA yang
 * dipakai bersama.
 */
class UniformStockTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_tier_hides_write_buttons_on_ga_stock_pages(): void
    {
        $branch = Branch::create(['name' => 'Test Outlet', 'code' => 'TST', 'is_active' => true]);
        Module::create(['key' => Module::UNIFORMS, 'label' => 'Seragam', 'is_active' => true]);

        $stock = UniformStock::create([
            'stock_code' => 'SRG-0001', 'uniform_type' => 'Kemeja', 'size' => 'M',
            'branch_id' => $branch->id, 'status' => UniformStock::STATUS_BAGUS,
            'available_stock' => 5, 'unusable_stock' => 0, 'low_stock_threshold' => 0,
        ]);

        $ga = User::factory()->create();
        $ga->roles()->attach(Role::firstOrCreate(['name' => Role::GA]));
        $ga->modules()->attach(Module::where('key', Module::UNIFORMS)->value('id'));
        $ga->pagePermissions()->create([
            'page_key' => UserPagePermission::PAGE_UNIFORMS_STOCKS,
            'access_level' => UserPagePermission::ACCESS_VIEW,
        ]);

        $index = $this->actingAs($ga)->get('/ga/uniforms/stocks');
        $index->assertOk();
        $index->assertDontSee('Buat Varian');

        $show = $this->actingAs($ga)->get("/ga/uniforms/stocks/{$stock->id}");
        $show->assertOk();
        $show->assertDontSee('Tambah Stok');
        $show->assertSee('5'); // stok tetap terlihat, cuma tombol tulis yang hilang

        $this->actingAs($ga)->get('/ga/uniforms/stocks/create')->assertForbidden();
    }

    public function test_edit_tier_still_shows_write_buttons(): void
    {
        $branch = Branch::create(['name' => 'Test Outlet', 'code' => 'TST', 'is_active' => true]);
        Module::create(['key' => Module::UNIFORMS, 'label' => 'Seragam', 'is_active' => true]);

        $ga = User::factory()->create();
        $ga->roles()->attach(Role::firstOrCreate(['name' => Role::GA]));
        $ga->modules()->attach(Module::where('key', Module::UNIFORMS)->value('id'));
        // Tidak ada baris page_permissions -> default 'edit'.

        $index = $this->actingAs($ga)->get('/ga/uniforms/stocks');

        $index->assertOk();
        $index->assertSee('Buat Varian');
        $this->actingAs($ga)->get('/ga/uniforms/stocks/create')->assertOk();
    }
}
