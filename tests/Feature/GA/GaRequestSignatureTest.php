<?php

namespace Tests\Feature\GA;

use App\Models\Branch;
use App\Models\Division;
use App\Models\GA\GaRequest;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cakupan tanda tangan digital GaRequest: (1) submit tanpa tanda tangan
 * pemohon ditolak, (2) draft bisa disimpan tanpa tanda tangan/item lengkap
 * lalu dilanjutkan, (3) hanya pemilik draft yang boleh edit, request yang
 * sudah submitted tidak bisa diedit lagi. Alur approval (Finance->Head->
 * Finance) dihapus total 24/08/2026 — GaRequest berhenti di "submitted"
 * begitu dokumen bisa dicetak, tidak ada lagi approve/reject/received.
 */
class GaRequestSignatureTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Division $division;

    private const TINY_PNG_DATA_URL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Outlet', 'code' => 'TST', 'is_active' => true]);
        $this->division = Division::create(['name' => 'General Affair', 'code' => Division::GA]);

        Module::create(['key' => Module::REQUESTS, 'label' => 'Asset Request', 'is_active' => true]);
    }

    private function requesterUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => Role::ADMIN]));

        return $user;
    }

    private function submitPayload(array $overrides = []): array
    {
        return array_merge([
            'intent' => 'submit',
            'requester_name' => 'QA Pemohon',
            'branch_id' => $this->branch->id,
            'category' => GaRequest::CATEGORY_OPERASIONAL_PENGEMBANGAN,
            'priority' => 'normal',
            'description' => 'QA test description',
            'items' => [
                ['item_name' => 'Item', 'type' => 'Barang', 'unit' => 'pcs', 'qty' => 1, 'price_per_unit' => 1000, 'vendor_name' => 'Vendor'],
            ],
        ], $overrides);
    }

    public function test_submit_without_requester_signature_is_rejected(): void
    {
        $requester = $this->requesterUser();

        $response = $this->actingAs($requester)->post('/ga/requests', $this->submitPayload());

        $response->assertSessionHasErrors('signature');
        $this->assertSame(0, GaRequest::count());
    }

    public function test_submit_with_drawn_signature_succeeds(): void
    {
        $requester = $this->requesterUser();

        $response = $this->actingAs($requester)->post('/ga/requests', $this->submitPayload([
            'signature_data' => self::TINY_PNG_DATA_URL,
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, GaRequest::count());
        $this->assertNotNull(GaRequest::first()->requester_signature_path);
    }

    public function test_draft_can_be_saved_without_signature_or_complete_items(): void
    {
        $requester = $this->requesterUser();

        $response = $this->actingAs($requester)->post('/ga/requests', $this->submitPayload([
            'intent' => 'draft',
            'items' => [
                ['item_name' => 'Item belum lengkap', 'type' => '', 'unit' => '', 'qty' => 1, 'price_per_unit' => 0, 'vendor_name' => ''],
            ],
        ]));

        $response->assertSessionHasNoErrors();
        $gaRequest = GaRequest::firstOrFail();
        $this->assertSame(GaRequest::STATUS_DRAFT, $gaRequest->status);
        $this->assertNull($gaRequest->requester_signature_path);
    }

    public function test_draft_can_be_edited_and_then_submitted_for_real(): void
    {
        $requester = $this->requesterUser();

        $this->actingAs($requester)->post('/ga/requests', $this->submitPayload(['intent' => 'draft']));
        $gaRequest = GaRequest::firstOrFail();

        $response = $this->actingAs($requester)->put("/ga/requests/{$gaRequest->id}", $this->submitPayload([
            'intent' => 'submit',
            'signature_data' => self::TINY_PNG_DATA_URL,
        ]));

        $response->assertSessionHasNoErrors();
        $gaRequest->refresh();
        $this->assertSame(GaRequest::STATUS_SUBMITTED, $gaRequest->status);
        $this->assertNotNull($gaRequest->requester_signature_path);
    }

    public function test_only_the_owner_can_edit_their_own_draft(): void
    {
        $requester = $this->requesterUser();
        $otherUser = $this->requesterUser();

        $this->actingAs($requester)->post('/ga/requests', $this->submitPayload(['intent' => 'draft']));
        $gaRequest = GaRequest::firstOrFail();

        $this->actingAs($otherUser)->get("/ga/requests/{$gaRequest->id}/edit")->assertForbidden();
    }

    public function test_a_submitted_request_can_no_longer_be_edited(): void
    {
        $requester = $this->requesterUser();

        $this->actingAs($requester)->post('/ga/requests', $this->submitPayload([
            'signature_data' => self::TINY_PNG_DATA_URL,
        ]));
        $gaRequest = GaRequest::firstOrFail();

        $this->actingAs($requester)->get("/ga/requests/{$gaRequest->id}/edit")->assertForbidden();
    }
}
