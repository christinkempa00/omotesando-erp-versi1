<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchaseOrderFromRequisitionRequest;
use App\Http\Requests\Purchasing\StorePurchaseOrderRequest;
use App\Models\Branch;
use App\Models\Purchasing\PurchaseOrder;
use App\Models\Purchasing\PurchaseRequisition;
use App\Models\Purchasing\Supplier;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Purchase Order. Dua jalur pembuatan (lihat PurchaseOrder model docblock):
 * - 'general' (barang umum): create()/store() di sini — dibuat LANGSUNG oleh
 *   GA, tanpa requisisi. Approval 3 step (Purchasing, Head, Finance).
 * - 'food' (bahan makanan): createFromRequisition()/storeFromRequisition() —
 *   dibuat Purchasing dari PurchaseRequisition yang sudah disetujui. Approval
 *   2 step (Head, Finance) — Purchasing sudah "menyetujui" lewat requisisi.
 * Step Head selalu lewat Approval Inbox generik (head.approvals.*). Step
 * Purchasing & Finance di-approve inline di halaman show() ini.
 */
class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::PURCHASING, Role::GA, Role::FINANCE, Role::GUDANG, Role::ADMIN),
            403
        );

        $query = PurchaseOrder::with(['supplier', 'branch', 'orderedBy'])->latest();

        // Purchasing & Finance butuh lihat SEMUA PO (Purchasing: pembuat PO
        // food + approver PO general; Finance: approver step terakhir
        // semua kategori) — sama seperti Admin.
        if (! $user->hasRole(Role::ADMIN, Role::FINANCE, Role::PURCHASING)) {
            if ($user->hasRole(Role::GUDANG)) {
                // Gudang butuh lihat SEMUA PO yang tujuannya branch miliknya
                // (bukan cuma yang dia buat sendiri) — supaya tahu apa yang
                // perlu diterima, sama seperti pola batches.index utk SCM.
                $query->where('branch_id', $user->branch_id);
            } elseif ($user->hasRole(Role::GA)) {
                // GA: tim back-office kecil, lihat semua PO kategori
                // 'general' (lihat authorizeView()), bukan cuma buatannya.
                $query->where('category', PurchaseOrder::CATEGORY_GENERAL);
            }
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('purchasing.purchase-orders.index', [
            'orders' => $orders,
            'statusLabels' => PurchaseOrder::statusLabels(),
            'categoryLabels' => PurchaseOrder::categoryLabels(),
            'selectedStatus' => $status,
        ]);
    }

    // --- Kategori 'general' (barang umum) — dibuat langsung oleh GA ---

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::GA, Role::ADMIN), 403);

        return view('purchasing.purchase-orders.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'branches' => Branch::orderedOutlets(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GA, Role::ADMIN), 403);

        $validated = $request->validated();

        $purchaseOrder = DB::transaction(function () use ($validated, $user) {
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'supplier_id' => $validated['supplier_id'],
                'branch_id' => $validated['branch_id'],
                'ordered_by' => $user->id,
                'category' => PurchaseOrder::CATEGORY_GENERAL,
                'order_date' => $validated['order_date'],
                'status' => PurchaseOrder::STATUS_SUBMITTED,
            ]);

            $this->createItems($purchaseOrder, $validated['items']);
            $purchaseOrder->generateApprovalSteps();

            return $purchaseOrder;
        });

        return redirect()
            ->route('purchasing.purchase-orders.show', $purchaseOrder)
            ->with('success', "Purchase Order {$purchaseOrder->po_number} berhasil dibuat.");
    }

    // --- Kategori 'food' (bahan makanan) — dibuat Purchasing dari PurchaseRequisition ---

    public function createFromRequisition(Request $request, PurchaseRequisition $purchaseRequisition): View
    {
        abort_unless($request->user()->hasRole(Role::PURCHASING, Role::ADMIN), 403);
        abort_unless($purchaseRequisition->status === PurchaseRequisition::STATUS_APPROVED, 422, 'Requisition ini belum disetujui.');
        abort_if($purchaseRequisition->purchaseOrders()->exists(), 422, 'Requisition ini sudah pernah dibuatkan PO.');

        $purchaseRequisition->load('items', 'branch');

        return view('purchasing.purchase-orders.create-from-requisition', [
            'purchaseRequisition' => $purchaseRequisition,
            'suppliers' => Supplier::orderBy('name')->get(),
            'branches' => Branch::orderedOutlets(),
        ]);
    }

    public function storeFromRequisition(StorePurchaseOrderFromRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::PURCHASING, Role::ADMIN), 403);
        abort_unless($purchaseRequisition->status === PurchaseRequisition::STATUS_APPROVED, 422, 'Requisition ini belum disetujui.');
        abort_if($purchaseRequisition->purchaseOrders()->exists(), 422, 'Requisition ini sudah pernah dibuatkan PO.');

        $validated = $request->validated();

        $purchaseOrder = DB::transaction(function () use ($validated, $user, $purchaseRequisition) {
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'supplier_id' => $validated['supplier_id'],
                'purchase_requisition_id' => $purchaseRequisition->id,
                'branch_id' => $validated['branch_id'],
                'ordered_by' => $user->id,
                'category' => PurchaseOrder::CATEGORY_FOOD,
                'order_date' => $validated['order_date'],
                'status' => PurchaseOrder::STATUS_SUBMITTED,
            ]);

            $this->createItems($purchaseOrder, $validated['items']);
            $purchaseOrder->generateApprovalSteps();

            return $purchaseOrder;
        });

        return redirect()
            ->route('purchasing.purchase-orders.show', $purchaseOrder)
            ->with('success', "Purchase Order {$purchaseOrder->po_number} berhasil dibuat dari {$purchaseRequisition->requisition_number}.");
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): View
    {
        $user = $request->user();
        $this->authorizeView($user, $purchaseOrder);

        $purchaseOrder->load([
            'supplier', 'branch', 'orderedBy', 'items', 'purchaseRequisition',
            'approvals.approver',
            'goodsReceipt.receivedBy', 'goodsReceipt.items.purchaseOrderItem',
            'goodsReceipt.supplierInvoice',
        ]);

        return view('purchasing.purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    /**
     * Approve/reject Purchasing (step 1, PO kategori 'general' saja) &
     * Finance (step terakhir, semua kategori) dilakukan di sini (inline).
     * Step Head TIDAK lewat sini — Head approve lewat Approval Inbox
     * generik (head.approvals.*). Sengaja TIDAK ada Role::ADMIN di gate ini
     * — Approvable::approveCurrentStepBy() cuma meloloskan user yang
     * benar-benar punya role approver step tsb, tidak ada konsep "Admin
     * override" di trait itu sama sekali.
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::PURCHASING, Role::FINANCE), 403);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $purchaseOrder->approveCurrentStepBy($request->user(), $validated['note'] ?? null)) {
            return back()->withErrors(['approval' => 'PO ini sudah diproses atau bukan giliran Anda.']);
        }

        return back()->with('success', 'Purchase Order berhasil disetujui.');
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::PURCHASING, Role::FINANCE), 403);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ], [
            'note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        if (! $purchaseOrder->rejectCurrentStepBy($request->user(), $validated['note'])) {
            return back()->withErrors(['approval' => 'PO ini sudah diproses atau bukan giliran Anda.']);
        }

        return back()->with('success', 'Purchase Order berhasil ditolak.');
    }

    private function createItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        foreach ($items as $item) {
            $purchaseOrder->items()->create([
                'item_name' => $item['item_name'],
                'qty' => $item['qty'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['qty'] * $item['unit_price'],
            ]);
        }
    }

    /**
     * Admin, Finance, & Purchasing lihat semua (Purchasing: pembuat PO food
     * + approver PO general; Finance: approver step terakhir semua
     * kategori). Gudang lihat PO yang tujuannya branch miliknya (perlu tahu
     * apa yang mau diterima). GA lihat SEMUA PO kategori 'general' (tim
     * back-office kecil, tidak per-outlet) — siapa pun stafnya bisa jadi
     * yang menerima barangnya, bukan cuma yang bikin PO-nya.
     */
    private function authorizeView(User $user, PurchaseOrder $purchaseOrder): void
    {
        if ($user->hasRole(Role::ADMIN, Role::FINANCE, Role::PURCHASING)) {
            return;
        }

        if ($user->hasRole(Role::GUDANG) && $purchaseOrder->branch_id === $user->branch_id) {
            return;
        }

        if ($user->hasRole(Role::GA) && $purchaseOrder->category === PurchaseOrder::CATEGORY_GENERAL) {
            return;
        }

        abort_unless($purchaseOrder->ordered_by === $user->id, 403);
    }
}
