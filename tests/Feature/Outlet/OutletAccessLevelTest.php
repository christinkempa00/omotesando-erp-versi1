<?php

namespace Tests\Feature\Outlet;

use App\Models\Branch;
use App\Models\Division;
use App\Models\GA\Asset;
use App\Models\GA\GaRequest;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPagePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Portal Outlet — role:Outlet ditambahkan lewat rute outlet.* yang menunjuk
 * ke controller GA yang SAMA (bukan controller baru, lihat routes/web.php),
 * diperluas dgn branch auto-scope + tier akses per halaman (view/edit, lihat
 * User::canEdit() & UserPagePermission). Cakupan test ini: (1) role selain
 * Outlet ditolak, (2) akun Outlet tanpa branch_id ditolak (EnsureOutletHasBranch),
 * (3) data ke-scope ke branch sendiri (tidak bocor ke branch lain),
 * (4) tier "view" memblokir aksi tulis, tier default (tidak ada baris)
 * tetap "edit" — tanpa regresi, (5) branch dipaksa server-side sekalipun
 * payload mencoba kirim branch lain.
 */
class OutletAccessLevelTest extends TestCase
{
    use RefreshDatabase;

    private Branch $ownBranch;

    private Branch $otherBranch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownBranch = Branch::create(['name' => 'Ask for Patty', 'code' => 'AFP', 'is_active' => true]);
        $this->otherBranch = Branch::create(['name' => 'Zodiac', 'code' => 'ZOD', 'is_active' => true]);

        Division::create(['name' => 'General Affair', 'code' => Division::GA]);

        Module::create(['key' => Module::REQUESTS, 'label' => 'Pengajuan', 'is_active' => true]);
        Module::create(['key' => Module::ASSETS, 'label' => 'Aset', 'is_active' => true]);
    }

    private function outletUser(?Branch $branch, array $moduleKeys = [Module::REQUESTS, Module::ASSETS]): User
    {
        $user = User::factory()->create(['branch_id' => $branch?->id]);
        $user->roles()->attach(Role::firstOrCreate(['name' => Role::OUTLET]));

        $moduleIds = Module::whereIn('key', $moduleKeys)->pluck('id');
        $user->modules()->attach($moduleIds);

        return $user;
    }

    private function gaUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => Role::ADMIN]));

        return $user;
    }

    private function requestPayload(array $overrides = []): array
    {
        return array_merge([
            'intent' => 'draft',
            'requester_name' => 'QA Outlet',
            'branch_id' => $this->ownBranch->id,
            'category' => GaRequest::CATEGORY_OPERASIONAL_PENGEMBANGAN,
            'priority' => 'normal',
            'description' => 'QA test description',
            'items' => [
                ['item_name' => 'Item', 'type' => 'Barang', 'unit' => 'pcs', 'qty' => 1, 'price_per_unit' => 1000, 'vendor_name' => 'Vendor'],
            ],
        ], $overrides);
    }

    public function test_role_other_than_outlet_is_forbidden(): void
    {
        $ga = $this->gaUser();

        $this->actingAs($ga)->get('/outlet/dashboard')->assertForbidden();
    }

    public function test_outlet_account_without_branch_is_blocked(): void
    {
        $outlet = $this->outletUser(null);

        $this->actingAs($outlet)->get('/outlet/dashboard')->assertForbidden();
    }

    public function test_outlet_only_sees_assets_from_own_branch(): void
    {
        $outlet = $this->outletUser($this->ownBranch);

        Asset::create([
            'asset_code' => 'AST-OWN', 'name' => 'Aset Milik Sendiri', 'branch_id' => $this->ownBranch->id,
            'status' => Asset::STATUS_BAGUS, 'quantity' => 1, 'created_by' => $outlet->id,
        ]);
        Asset::create([
            'asset_code' => 'AST-OTHER', 'name' => 'Aset Outlet Lain', 'branch_id' => $this->otherBranch->id,
            'status' => Asset::STATUS_BAGUS, 'quantity' => 1, 'created_by' => $outlet->id,
        ]);

        $response = $this->actingAs($outlet)->get('/outlet/assets');

        $response->assertOk();
        $response->assertSee('Aset Milik Sendiri');
        $response->assertDontSee('Aset Outlet Lain');
    }

    public function test_default_tier_is_edit_and_allows_creating_a_request(): void
    {
        $outlet = $this->outletUser($this->ownBranch);

        $response = $this->actingAs($outlet)->post('/outlet/requests', $this->requestPayload());

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, GaRequest::count());
    }

    public function test_view_tier_blocks_creating_a_request(): void
    {
        $outlet = $this->outletUser($this->ownBranch);
        $outlet->pagePermissions()->create([
            'page_key' => UserPagePermission::PAGE_REQUESTS,
            'access_level' => UserPagePermission::ACCESS_VIEW,
        ]);

        $response = $this->actingAs($outlet)->post('/outlet/requests', $this->requestPayload());

        $response->assertForbidden();
        $this->assertSame(0, GaRequest::count());
    }

    public function test_branch_is_forced_even_if_payload_sends_another_branch(): void
    {
        $outlet = $this->outletUser($this->ownBranch);

        $response = $this->actingAs($outlet)->post('/outlet/requests', $this->requestPayload([
            'branch_id' => $this->otherBranch->id,
        ]));

        $response->assertSessionHasNoErrors();
        $gaRequest = GaRequest::firstOrFail();
        $this->assertSame($this->ownBranch->id, $gaRequest->branch_id);
    }
}
