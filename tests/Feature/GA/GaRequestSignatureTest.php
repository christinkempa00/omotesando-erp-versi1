<?php

namespace Tests\Feature\GA;

use App\Models\Approval;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\TransactionAccountMapping;
use App\Models\GA\GaRequest;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cakupan pilot tanda tangan digital GaRequest (Tugas 1-4):
 * (1) submit tanpa tanda tangan pemohon ditolak,
 * (2) approve tanpa tanda tangan approver ditolak,
 * (3) notifikasi Telegram (approval step & dokumen final) SENGAJA dihapus
 *     dari GaRequest (Revisi V1 — bot Telegram sekarang eksklusif utk Jadwal
 *     Pemeliharaan) — test memastikan sendDocument/sendMessage TIDAK pernah
 *     terpanggil lagi, bahkan setelah fully approved,
 * (4) tanda tangan tersimpan di profil user dipakai ulang otomatis sbg
 *     default saat approve berikutnya (tidak perlu gambar ulang).
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

        config([
            'services.telegram.bot_token' => 'test-bot-token',
            'services.telegram.chat_id' => 'test-group-chat-id',
        ]);
    }

    private function requesterUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => Role::ADMIN]));

        return $user;
    }

    private function financeUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => Role::FINANCE]));

        return $user;
    }

    private function headUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => Role::HEAD]));

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

    /**
     * Buat GaRequest siap uji, sudah lolos step 1 (Finance), sehingga
     * berhenti di step 2 (Head) — persis kondisi yang dibutuhkan tes
     * approve/reject Head.
     */
    private function createGaRequestAtHeadStep(User $requester, User $finance): GaRequest
    {
        $gaRequest = GaRequest::create([
            'request_number' => GaRequest::generateRequestNumber(),
            'division_id' => $this->division->id,
            'branch_id' => $this->branch->id,
            'category' => GaRequest::CATEGORY_OPERASIONAL_PENGEMBANGAN,
            'priority' => 'normal',
            'description' => 'QA test',
            'requested_by' => $requester->id,
            'requester_name' => $requester->name,
            'requester_signature_path' => 'ga-requests/signatures/dummy.png',
            'status' => GaRequest::STATUS_SUBMITTED,
            'total_amount' => 1000,
        ]);
        $gaRequest->items()->create([
            'item_name' => 'Item', 'type' => 'Barang', 'unit' => 'pcs',
            'qty' => 1, 'price_per_unit' => 1000, 'total' => 1000, 'vendor_name' => 'Vendor',
        ]);
        $gaRequest->generateApprovalSteps();
        $gaRequest->approveCurrentStepBy($finance, 'auto step 1');

        return $gaRequest;
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
        $this->assertSame(0, $gaRequest->approvals()->count());
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
        $this->assertSame(3, $gaRequest->approvals()->count());
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

    public function test_approve_without_approver_signature_is_rejected(): void
    {
        $requester = $this->requesterUser();
        $finance = $this->financeUser();
        $head = $this->headUser();

        $gaRequest = $this->createGaRequestAtHeadStep($requester, $finance);

        $response = $this->actingAs($head)->post("/head/requests/{$gaRequest->id}/approve", [
            'note' => 'no signature attached',
        ]);

        $response->assertSessionHasErrors('signature');

        $step2 = $gaRequest->approvals()->where('step', 2)->first();
        $this->assertSame(Approval::STATUS_PENDING, $step2->status);
    }

    public function test_reject_does_not_require_a_signature(): void
    {
        $requester = $this->requesterUser();
        $finance = $this->financeUser();
        $head = $this->headUser();

        $gaRequest = $this->createGaRequestAtHeadStep($requester, $finance);

        $response = $this->actingAs($head)->post("/head/requests/{$gaRequest->id}/reject", [
            'note' => 'alasan penolakan',
        ]);

        $response->assertSessionHasNoErrors();

        $step2 = $gaRequest->approvals()->where('step', 2)->first();
        $this->assertSame(Approval::STATUS_REJECTED, $step2->status);
        $this->assertNull($step2->signature_path);
    }

    /**
     * Step 3 (Finance) memicu GaRequestObserver -> JournalPoster, yang
     * firstOrFail() ke transaction_account_mappings (lihat README "Kalau
     * mapping akun... belum ada, aksi terkait akan GAGAL"). Ini bagian
     * Finance yang SUDAH ADA, bukan sesuatu yang boleh disentuh Tugas ini —
     * jadi di sini cukup seed mapping minimal yang production juga selalu
     * punya (TransactionAccountMappingSeeder), supaya step 3 bisa lolos.
     */
    private function seedGaRequestReceivedMapping(): void
    {
        $debit = ChartOfAccount::create(['code' => '5100', 'name' => 'Beban Operasional', 'type' => ChartOfAccount::TYPE_EXPENSE]);
        $credit = ChartOfAccount::create(['code' => '1100', 'name' => 'Kas', 'type' => ChartOfAccount::TYPE_ASSET]);

        TransactionAccountMapping::create([
            'transaction_type' => TransactionAccountMapping::TYPE_GA_REQUEST_RECEIVED,
            'debit_account_id' => $debit->id,
            'credit_account_id' => $credit->id,
        ]);
    }

    public function test_no_telegram_notification_is_sent_even_when_fully_approved(): void
    {
        $this->seedGaRequestReceivedMapping();

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $requester = $this->requesterUser();
        $finance = $this->financeUser();
        $head = $this->headUser();

        $gaRequest = $this->createGaRequestAtHeadStep($requester, $finance);

        // Step 2 (Head approve, WITH signature) — masih ada step 3 (Finance)
        // sesudahnya, jadi belum fully approved.
        $this->actingAs($head)->post("/head/requests/{$gaRequest->id}/approve", [
            'note' => 'approved by head',
            'signature_data' => self::TINY_PNG_DATA_URL,
        ])->assertSessionHasNoErrors();

        // Step 3 (Finance, langsung lewat model — jalur approve Finance
        // belum ada UI-nya sendiri, sama seperti alur produksi asli).
        $gaRequest->approveCurrentStepBy($finance, 'final finance step');

        $this->assertSame(GaRequest::STATUS_RECEIVED, $gaRequest->fresh()->status);

        // Bot Telegram sekarang eksklusif utk Jadwal Pemeliharaan — GaRequest
        // tidak lagi memanggil Telegram sama sekali, di step manapun.
        Http::assertNothingSent();
    }

    public function test_saved_signature_is_reused_as_default_on_next_approve(): void
    {
        $requester = $this->requesterUser();
        $finance = $this->financeUser();
        $head = $this->headUser();

        $gaRequestA = $this->createGaRequestAtHeadStep($requester, $finance);

        $this->actingAs($head)->post("/head/requests/{$gaRequestA->id}/approve", [
            'note' => 'first approve, drawing new signature',
            'signature_data' => self::TINY_PNG_DATA_URL,
        ])->assertSessionHasNoErrors();

        $head->refresh();
        $this->assertNotNull($head->signature_path);
        $savedPath = $head->signature_path;

        $gaRequestB = $this->createGaRequestAtHeadStep($requester, $finance);

        $this->actingAs($head)->post("/head/requests/{$gaRequestB->id}/approve", [
            'note' => 'second approve, reusing saved signature',
            'signature_use_saved' => '1',
        ])->assertSessionHasNoErrors();

        $step2B = $gaRequestB->approvals()->where('step', 2)->first();
        $this->assertSame(Approval::STATUS_APPROVED, $step2B->status);
        $this->assertSame($savedPath, $step2B->signature_path);
    }
}
