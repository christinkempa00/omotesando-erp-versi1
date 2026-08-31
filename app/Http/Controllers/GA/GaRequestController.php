<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\StoreGaRequestRequest;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\Division;
use App\Models\GA\GaRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPagePermission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class GaRequestController extends Controller
{
    /**
     * Staff GA hanya lihat pengajuan miliknya sendiri.
     * Head & Admin lihat semua pengajuan (untuk kebutuhan monitoring).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = GaRequest::with(['branch', 'branchLocation', 'requestedBy'])
            ->latest();

        if (! $user->hasRole(Role::HEAD, Role::ADMIN)) {
            $query->where('requested_by', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(15)->withQueryString();

        return $this->viewFor('requests.index', [
            'requests' => $requests,
            'statusLabels' => GaRequest::statusLabels(),
            'selectedStatus' => $status,
            'search' => $search,
            'branches' => Branch::orderedOutlets(Branch::GA_OUTLETS),
            'branchLocations' => BranchLocation::groupedByBranch(),
        ]);
    }

    public function create(Request $request): View
    {
        $branches = Branch::orderedOutlets(Branch::GA_OUTLETS);
        if ($userBranch = $request->user()->scopingBranch()) {
            $branches = $branches->filter(fn (Branch $b) => $b->id === $userBranch->id)->values();
        }

        return $this->viewFor('requests.create', [
            'categoryLabels' => GaRequest::categoryLabels(),
            'priorityLabels' => GaRequest::priorityLabels(),
            'branches' => $branches,
            'branchLocations' => BranchLocation::groupedByBranch(),
            'savedSignatureUrl' => $this->savedSignatureUrl($request->user()),
        ]);
    }

    public function store(StoreGaRequestRequest $request): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_REQUESTS), 403);

        $user = $request->user();
        $validated = $request->validated();
        $intent = $validated['intent'];
        $requesterSignaturePath = $this->resolveRequesterSignaturePath($request, $validated);

        // Branch = identitas user, dipaksa terlepas dari input form (lihat
        // prinsip di plan "Tier akses per halaman").
        if ($userBranch = $user->scopingBranch()) {
            $validated['branch_id'] = $userBranch->id;
            if (
                ($validated['branch_location_id'] ?? null)
                && ! BranchLocation::where('id', $validated['branch_location_id'])->where('branch_id', $userBranch->id)->exists()
            ) {
                $validated['branch_location_id'] = null;
            }
        }

        $gaRequest = DB::transaction(function () use ($validated, $user, $requesterSignaturePath, $intent) {
            $gaRequest = GaRequest::create([
                'request_number' => GaRequest::generateRequestNumber(),
                // Staf GA/Admin/Finance yang mengajukan sering tidak py
                // division_id sendiri (cuma Produksi yg diberi division di
                // seeder) — default ke divisi GA supaya insert tidak gagal
                // (division_id NOT NULL), bukan bug di modul ini seharusnya
                // memblokir submit sama sekali.
                'division_id' => $user->division_id ?? Division::where('code', Division::GA)->value('id'),
                'branch_id' => $validated['branch_id'],
                'branch_location_id' => $validated['branch_location_id'] ?? null,
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'description' => $validated['description'],
                'requested_by' => $user->id,
                'requester_name' => $validated['requester_name'],
                'requester_signature_path' => $requesterSignaturePath,
                'status' => $intent === 'submit' ? GaRequest::STATUS_SUBMITTED : GaRequest::STATUS_DRAFT,
                'discount_percent' => $validated['discount_percent'] ?? null,
                'pph_percent' => $validated['pph_percent'] ?? null,
            ]);

            $this->syncItems($gaRequest, $validated['items']);
            $gaRequest->recalculateTotal();

            return $gaRequest;
        });

        $this->storeAttachments($request, $gaRequest);

        return redirect()
            ->route($this->routeFor('requests.show'), $gaRequest)
            ->with('success', $this->statusMessage($gaRequest, $intent));
    }

    /**
     * Hanya draft milik sendiri yang boleh dibuka utk dilanjutkan — begitu
     * status berubah jadi submitted (dokumen sudah final), tidak ada lagi
     * jalur edit lewat sini.
     */
    public function edit(Request $request, GaRequest $gaRequest): View
    {
        $user = $request->user();

        abort_unless(
            $gaRequest->status === GaRequest::STATUS_DRAFT && $gaRequest->requested_by === $user->id,
            403
        );

        $gaRequest->load(['items', 'attachments']);

        $branches = Branch::orderedOutlets(Branch::GA_OUTLETS);
        if ($userBranch = $user->scopingBranch()) {
            $branches = $branches->filter(fn (Branch $b) => $b->id === $userBranch->id)->values();
        }

        return $this->viewFor('requests.edit', [
            'gaRequest' => $gaRequest,
            'categoryLabels' => GaRequest::categoryLabels(),
            'priorityLabels' => GaRequest::priorityLabels(),
            'branches' => $branches,
            'branchLocations' => BranchLocation::groupedByBranch(),
            'savedSignatureUrl' => $this->savedSignatureUrl($user),
        ]);
    }

    public function update(StoreGaRequestRequest $request, GaRequest $gaRequest): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $gaRequest->status === GaRequest::STATUS_DRAFT && $gaRequest->requested_by === $user->id,
            403
        );
        abort_unless($user->canEdit(UserPagePermission::PAGE_REQUESTS), 403);

        $validated = $request->validated();
        $intent = $validated['intent'];
        $requesterSignaturePath = $this->resolveRequesterSignaturePath($request, $validated);

        if ($userBranch = $user->scopingBranch()) {
            $validated['branch_id'] = $userBranch->id;
            if (
                ($validated['branch_location_id'] ?? null)
                && ! BranchLocation::where('id', $validated['branch_location_id'])->where('branch_id', $userBranch->id)->exists()
            ) {
                $validated['branch_location_id'] = null;
            }
        }

        DB::transaction(function () use ($gaRequest, $validated, $intent, $requesterSignaturePath) {
            $gaRequest->update([
                'branch_id' => $validated['branch_id'],
                'branch_location_id' => $validated['branch_location_id'] ?? null,
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'description' => $validated['description'],
                'requester_name' => $validated['requester_name'],
                'requester_signature_path' => $requesterSignaturePath ?? $gaRequest->requester_signature_path,
                'status' => $intent === 'submit' ? GaRequest::STATUS_SUBMITTED : GaRequest::STATUS_DRAFT,
                'discount_percent' => $validated['discount_percent'] ?? null,
                'pph_percent' => $validated['pph_percent'] ?? null,
            ]);

            $gaRequest->items()->delete();
            $this->syncItems($gaRequest, $validated['items']);
            $gaRequest->recalculateTotal();
        });

        $this->storeAttachments($request, $gaRequest);

        return redirect()
            ->route($this->routeFor('requests.show'), $gaRequest)
            ->with('success', $this->statusMessage($gaRequest, $intent));
    }

    private function statusMessage(GaRequest $gaRequest, string $intent): string
    {
        return $intent === 'submit'
            ? "Pengajuan {$gaRequest->request_number} berhasil dikirim."
            : "Draft pengajuan {$gaRequest->request_number} berhasil disimpan.";
    }

    private function savedSignatureUrl(User $user): ?string
    {
        return $user->signature_path ? Storage::url($user->signature_path) : null;
    }

    private function syncItems(GaRequest $gaRequest, array $items): void
    {
        foreach ($items as $item) {
            $gaRequest->items()->create([
                'item_name' => $item['item_name'],
                'type' => $item['type'] ?? null,
                'unit' => $item['unit'] ?? 'Pcs',
                'qty' => $item['qty'],
                'price_per_unit' => $item['price_per_unit'] ?? 0,
                'vendor_name' => $item['vendor_name'] ?? null,
            ]);
        }
    }

    private function storeAttachments(Request $request, GaRequest $gaRequest): void
    {
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $photo) {
                $gaRequest->attachments()->create([
                    'photo_path' => $photo->store('ga-requests/attachments', 'public'),
                ]);
            }
        }
    }

    /**
     * FormRequest::withValidator() sudah memastikan salah satu dari 2
     * sumber ini valid (gambar baru ATAU tersimpan+ada) KALAU intent=submit
     * — kalau intent=draft tidak ada jaminan itu, jadi bisa saja tidak ada
     * tanda tangan sama sekali (return null, bukan error).
     */
    private function resolveRequesterSignaturePath(Request $request, array $validated): ?string
    {
        $user = $request->user();

        if (($validated['signature_use_saved'] ?? false) && $user->signature_path) {
            return $user->signature_path;
        }

        if (empty($validated['signature_data'])) {
            return null;
        }

        $path = $this->storeSignatureImage($validated['signature_data']);

        if ($path && ($validated['signature_save_as_default'] ?? false)) {
            $user->update(['signature_path' => $path]);
        }

        return $path;
    }

    /**
     * Decode data URL PNG (dari canvas tanda tangan) & simpan ke disk public.
     * Dulu lewat Approval::storeSignatureImage() — dipindah ke sini setelah
     * sistem approval dihapus 24/08/2026 (GaRequest tidak lagi punya alasan
     * bergantung ke model Approval sama sekali).
     */
    private function storeSignatureImage(?string $signatureData): ?string
    {
        if (! $signatureData || ! preg_match('/^data:image\/png;base64,(.+)$/', $signatureData, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[1]);
        if ($binary === false) {
            return null;
        }

        $path = 'ga-requests/signatures/'.Str::random(32).'.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public function show(Request $request, GaRequest $gaRequest): View
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole(Role::HEAD, Role::ADMIN) || $gaRequest->requested_by === $user->id,
            403
        );

        $gaRequest->load(['items', 'branch', 'branchLocation', 'division', 'requestedBy', 'attachments']);

        return $this->viewFor('requests.show', [
            'gaRequest' => $gaRequest,
            'categoryLabels' => GaRequest::categoryLabels(),
            'priorityLabels' => GaRequest::priorityLabels(),
        ]);
    }

    /**
     * Dokumen GAR resmi (PDF) — dibangun dari data pengajuan, mengikuti
     * format dokumen GAR fisik yang lama.
     */
    public function document(Request $request, GaRequest $gaRequest): Response
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole(Role::HEAD, Role::ADMIN) || $gaRequest->requested_by === $user->id,
            403
        );

        $gaRequest->load(['items', 'branch', 'branchLocation', 'requestedBy']);

        $pdf = Pdf::loadView('ga.requests.document-pdf', [
            'gaRequest' => $gaRequest,
            'categoryLabels' => GaRequest::categoryLabels(),
        ])->setPaper('a4', 'portrait');

        $filename = 'GAR-'.str_replace('/', '-', $gaRequest->request_number).'.pdf';

        // stream(), bukan download() — tampil preview inline di tab
        // browser dulu (Content-Disposition: inline), user cetak/unduh
        // sendiri dari situ kalau memang sudah cek isinya.
        return $pdf->stream($filename);
    }

    public function destroy(Request $request, GaRequest $gaRequest): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole(Role::ADMIN) || $gaRequest->requested_by === $user->id,
            403
        );
        abort_unless($user->canEdit(UserPagePermission::PAGE_REQUESTS), 403);

        foreach ($gaRequest->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->photo_path);
        }

        $gaRequest->delete();

        return redirect()
            ->route('ga.requests.index')
            ->with('success', "Pengajuan {$gaRequest->request_number} berhasil dihapus.");
    }
}
