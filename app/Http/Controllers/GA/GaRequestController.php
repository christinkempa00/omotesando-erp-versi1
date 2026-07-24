<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\StoreGaRequestRequest;
use App\Models\Branch;
use App\Models\GA\GaRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GaRequestController extends Controller
{
    /**
     * Staff GA hanya lihat pengajuan miliknya sendiri.
     * Head & Admin lihat semua pengajuan (untuk kebutuhan monitoring),
     * meskipun aksi approve/reject belum dibangun di tahap ini.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = GaRequest::with(['branch', 'requestedBy'])
            ->latest();

        if (! $user->hasRole(Role::HEAD, Role::ADMIN)) {
            $query->where('requested_by', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('ga.requests.index', [
            'requests' => $requests,
            'statusLabels' => GaRequest::statusLabels(),
            'selectedStatus' => $status,
        ]);
    }

    public function create(): View
    {
        return view('ga.requests.create', [
            'categoryLabels' => GaRequest::categoryLabels(),
            'branches' => Branch::where('is_active', true)->get(),
        ]);
    }

    public function store(StoreGaRequestRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $gaRequest = DB::transaction(function () use ($validated, $user) {
            $gaRequest = GaRequest::create([
                'request_number' => GaRequest::generateRequestNumber(),
                'division_id' => $user->division_id,
                'branch_id' => $validated['branch_id'],
                'category' => $validated['category'],
                'description' => $validated['description'],
                'requested_by' => $user->id,
                'status' => GaRequest::STATUS_SUBMITTED,
            ]);

            foreach ($validated['items'] as $item) {
                $gaRequest->items()->create([
                    'item_name' => $item['item_name'],
                    'qty' => $item['qty'],
                    'price_per_unit' => $item['price_per_unit'],
                    'vendor_name' => $item['vendor_name'] ?? null,
                ]);
            }

            $gaRequest->generateApprovalSteps();

            return $gaRequest;
        });

        return redirect()
            ->route('ga.requests.show', $gaRequest)
            ->with('success', "Pengajuan {$gaRequest->request_number} berhasil dibuat.");
    }

    public function show(Request $request, GaRequest $gaRequest): View
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole(Role::HEAD, Role::ADMIN, Role::FINANCE) || $gaRequest->requested_by === $user->id,
            403
        );

        $gaRequest->load(['items', 'branch', 'division', 'requestedBy', 'approvals.approver']);

        return view('ga.requests.show', [
            'gaRequest' => $gaRequest,
            'categoryLabels' => GaRequest::categoryLabels(),
        ]);
    }
}
