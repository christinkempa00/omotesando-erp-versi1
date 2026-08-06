<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchaseRequisitionRequest;
use App\Models\Purchasing\PurchaseRequisition;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Purchase Requisition — diajukan Outlet utk bahan makanan, disetujui/
 * ditolak Purchasing (1 step). Kalau approved, Purchasing bikin PurchaseOrder
 * dari sini (lihat PurchaseOrderController::createFromRequisition). Pola sama
 * persis dengan MaterialRequestController di modul SCM.
 */
class PurchaseRequisitionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::OUTLET, Role::PURCHASING, Role::ADMIN), 403);

        $query = PurchaseRequisition::with(['branch', 'requestedBy'])->latest();

        if (! $user->hasRole(Role::PURCHASING, Role::ADMIN)) {
            $query->where('requested_by', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $requisitions = $query->paginate(15)->withQueryString();

        return view('purchasing.purchase-requisitions.index', [
            'requisitions' => $requisitions,
            'statusLabels' => PurchaseRequisition::statusLabels(),
            'selectedStatus' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::OUTLET), 403);

        return view('purchasing.purchase-requisitions.create');
    }

    public function store(StorePurchaseRequisitionRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::OUTLET), 403);
        abort_if(! $user->branch_id, 422, 'Akun Outlet Anda belum terikat ke satu branch — hubungi Admin.');

        $validated = $request->validated();

        $requisition = DB::transaction(function () use ($validated, $user) {
            $requisition = PurchaseRequisition::create([
                'requisition_number' => PurchaseRequisition::generateRequisitionNumber(),
                'branch_id' => $user->branch_id,
                'requested_by' => $user->id,
                'status' => PurchaseRequisition::STATUS_SUBMITTED,
            ]);

            foreach ($validated['items'] as $item) {
                $requisition->items()->create($item);
            }

            $requisition->generateApprovalSteps();

            return $requisition;
        });

        return redirect()
            ->route('purchasing.purchase-requisitions.show', $requisition)
            ->with('success', "Purchase Requisition {$requisition->requisition_number} berhasil dibuat.");
    }

    public function show(Request $request, PurchaseRequisition $purchaseRequisition): View
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::PURCHASING, Role::ADMIN) || $purchaseRequisition->requested_by === $user->id,
            403
        );

        $purchaseRequisition->load(['items', 'branch', 'requestedBy', 'approvals.approver', 'purchaseOrders']);

        return view('purchasing.purchase-requisitions.show', [
            'purchaseRequisition' => $purchaseRequisition,
        ]);
    }

    public function approve(Request $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::PURCHASING), 403);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $purchaseRequisition->approveCurrentStepBy($request->user(), $validated['note'] ?? null)) {
            return back()->withErrors(['approval' => 'Requisition ini sudah diproses atau bukan giliran Anda.']);
        }

        return back()->with('success', 'Purchase Requisition berhasil disetujui.');
    }

    public function reject(Request $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::PURCHASING), 403);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ], [
            'note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        if (! $purchaseRequisition->rejectCurrentStepBy($request->user(), $validated['note'])) {
            return back()->withErrors(['approval' => 'Requisition ini sudah diproses atau bukan giliran Anda.']);
        }

        return back()->with('success', 'Purchase Requisition berhasil ditolak.');
    }
}
