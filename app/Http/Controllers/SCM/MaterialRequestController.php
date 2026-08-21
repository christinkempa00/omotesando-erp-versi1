<?php

namespace App\Http\Controllers\SCM;

use App\Http\Controllers\Controller;
use App\Http\Requests\SCM\StoreMaterialRequestRequest;
use App\Models\Branch;
use App\Models\Role;
use App\Models\SCM\MaterialRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengajuan Bahan — dibuat Produksi, disetujui/ditolak Admin (1 step, lihat
 * MaterialRequest::generateApprovalSteps). Pola sama dengan GaRequestController.
 */
class MaterialRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->hasRole(Role::PRODUKSI, Role::ADMIN), 403);

        $query = MaterialRequest::with(['branch', 'requestedBy'])->latest();

        if (! $user->hasRole(Role::ADMIN)) {
            $query->where('requested_by', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('scm.materials.index', [
            'requests' => $requests,
            'statusLabels' => MaterialRequest::statusLabels(),
            'selectedStatus' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::PRODUKSI), 403);

        return view('scm.materials.create', [
            'branches' => Branch::orderedOutlets(),
        ]);
    }

    public function store(StoreMaterialRequestRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::PRODUKSI), 403);

        $validated = $request->validated();

        $materialRequest = DB::transaction(function () use ($validated, $user) {
            $materialRequest = MaterialRequest::create([
                'request_number' => MaterialRequest::generateRequestNumber(),
                'division_id' => $user->division_id,
                'branch_id' => $validated['branch_id'],
                'requested_by' => $user->id,
                'description' => $validated['description'] ?? null,
                'status' => MaterialRequest::STATUS_SUBMITTED,
            ]);

            foreach ($validated['items'] as $item) {
                $materialRequest->items()->create($item);
            }

            $materialRequest->generateApprovalSteps();

            return $materialRequest;
        });

        return redirect()
            ->route('scm.materials.show', $materialRequest)
            ->with('success', "Pengajuan Bahan {$materialRequest->request_number} berhasil dibuat.");
    }

    public function show(Request $request, MaterialRequest $materialRequest): View
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole(Role::ADMIN) || $materialRequest->requested_by === $user->id,
            403
        );

        $materialRequest->load(['items', 'branch', 'division', 'requestedBy', 'approvals.approver', 'productionBatches']);

        return view('scm.materials.show', [
            'materialRequest' => $materialRequest,
        ]);
    }

    public function approve(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $materialRequest->approveCurrentStepBy($request->user(), $validated['note'] ?? null)) {
            return back()->withErrors(['approval' => 'Pengajuan ini sudah diproses atau bukan giliran Anda.']);
        }

        return back()->with('success', 'Pengajuan bahan berhasil disetujui.');
    }

    public function reject(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ], [
            'note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        if (! $materialRequest->rejectCurrentStepBy($request->user(), $validated['note'])) {
            return back()->withErrors(['approval' => 'Pengajuan ini sudah diproses atau bukan giliran Anda.']);
        }

        return back()->with('success', 'Pengajuan bahan berhasil ditolak.');
    }

    /**
     * Bukti Pengambilan Bahan (PDF).
     */
    public function document(Request $request, MaterialRequest $materialRequest): Response
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole(Role::ADMIN) || $materialRequest->requested_by === $user->id,
            403
        );

        $materialRequest->load(['items', 'branch', 'requestedBy', 'approvals.approver']);

        $pdf = Pdf::loadView('scm.materials.document-pdf', [
            'materialRequest' => $materialRequest,
        ])->setPaper('a4', 'portrait');

        $filename = 'PB-'.str_replace('/', '-', $materialRequest->request_number).'.pdf';

        return $pdf->stream($filename);
    }
}
