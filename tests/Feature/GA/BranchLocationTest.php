<?php

namespace Tests\Feature\GA;

use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\Division;
use App\Models\GA\GaRequest;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cakupan field "Cabang" (sub-lokasi outlet, mis. Ask for Patty ->
 * Gandaria City) lewat GaRequestController — mewakili pola validasi/
 * penyimpanan yang sama dipakai di 6 controller GA lain (Asset, WorkLog,
 * MaintenanceJob, UniformStock, UniformRecord, GaQuickRequest). Field ini
 * SELALU opsional (nullable) — outlet tanpa Cabang terdaftar tetap harus
 * bisa disimpan tanpa branch_location_id sama sekali.
 */
class BranchLocationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchWithLocation;

    private Branch $branchWithoutLocation;

    private BranchLocation $location;

    private Division $division;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchWithLocation = Branch::create(['name' => 'Ask for Patty', 'code' => 'AFP', 'is_active' => true]);
        $this->branchWithoutLocation = Branch::create(['name' => 'Ironiku', 'code' => 'IRO', 'is_active' => true]);
        $this->location = BranchLocation::create([
            'branch_id' => $this->branchWithLocation->id,
            'name' => 'Gandaria City',
            'is_active' => true,
        ]);

        $this->division = Division::create(['name' => 'General Affair', 'code' => Division::GA]);

        Module::create(['key' => Module::REQUESTS, 'label' => 'Asset Request', 'is_active' => true]);
    }

    private function requesterUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => Role::ADMIN]));

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'intent' => 'submit',
            'requester_name' => 'QA Pemohon',
            'branch_id' => $this->branchWithLocation->id,
            'category' => GaRequest::CATEGORY_OPERASIONAL_PENGEMBANGAN,
            'priority' => 'normal',
            'description' => 'QA test description',
            'items' => [
                ['item_name' => 'Item', 'type' => 'Barang', 'unit' => 'pcs', 'qty' => 1, 'price_per_unit' => 1000, 'vendor_name' => 'Vendor'],
            ],
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        ], $overrides);
    }

    public function test_submitting_a_valid_branch_location_persists_and_labels_correctly(): void
    {
        $requester = $this->requesterUser();

        $response = $this->actingAs($requester)->post('/ga/requests', $this->payload([
            'branch_location_id' => $this->location->id,
        ]));

        $response->assertSessionHasNoErrors();
        $gaRequest = GaRequest::firstOrFail();
        $this->assertSame($this->location->id, $gaRequest->branch_location_id);
        $this->assertSame('Ask for Patty — Gandaria City', $gaRequest->outletLabel());
    }

    public function test_branch_location_belonging_to_a_different_branch_is_rejected(): void
    {
        $requester = $this->requesterUser();

        $response = $this->actingAs($requester)->post('/ga/requests', $this->payload([
            'branch_id' => $this->branchWithoutLocation->id,
            'branch_location_id' => $this->location->id,
        ]));

        $response->assertSessionHasErrors('branch_location_id');
        $this->assertSame(0, GaRequest::count());
    }

    public function test_branch_without_any_locations_can_be_submitted_with_null_branch_location(): void
    {
        $requester = $this->requesterUser();

        $response = $this->actingAs($requester)->post('/ga/requests', $this->payload([
            'branch_id' => $this->branchWithoutLocation->id,
        ]));

        $response->assertSessionHasNoErrors();
        $gaRequest = GaRequest::firstOrFail();
        $this->assertNull($gaRequest->branch_location_id);
        $this->assertSame('Ironiku', $gaRequest->outletLabel());
    }
}
