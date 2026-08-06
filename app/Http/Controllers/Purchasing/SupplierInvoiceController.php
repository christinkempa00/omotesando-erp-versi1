<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StoreSupplierInvoiceRequest;
use App\Http\Requests\Purchasing\UpdateSupplierInvoiceRequest;
use App\Models\Purchasing\GoodsReceipt;
use App\Models\Purchasing\SupplierInvoice;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Invoice supplier & pencatatan pembayarannya — domain Finance sepenuhnya
 * (baru muncul SETELAH barang diterima, lihat GoodsReceipt). Terpisah dari
 * alur approval PO (Head/Finance approve dulu SEBELUM barang dibeli;
 * invoice ini soal penagihan SETELAH barang sampai).
 */
class SupplierInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::FINANCE, Role::ADMIN), 403);

        $query = SupplierInvoice::with(['goodsReceipt.purchaseOrder.supplier', 'goodsReceipt.purchaseOrder.branch'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('purchasing.supplier-invoices.index', [
            'invoices' => $invoices,
            'statusLabels' => SupplierInvoice::statusLabels(),
            'selectedStatus' => $status,
        ]);
    }

    public function store(StoreSupplierInvoiceRequest $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::FINANCE, Role::ADMIN), 403);
        abort_if($goodsReceipt->supplierInvoice()->exists(), 422, 'Goods receipt ini sudah punya invoice.');

        $goodsReceipt->supplierInvoice()->create($request->validated() + [
            'status' => SupplierInvoice::STATUS_UNPAID,
            'paid_amount' => 0,
        ]);

        return redirect()
            ->route('purchasing.purchase-orders.show', $goodsReceipt->purchase_order_id)
            ->with('success', 'Invoice supplier berhasil dicatat.');
    }

    /**
     * Catat pembayaran baru — DITAMBAHKAN ke paid_amount yang sudah ada
     * (mendukung pembayaran bertahap), status disesuaikan otomatis.
     */
    public function recordPayment(UpdateSupplierInvoiceRequest $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::FINANCE, Role::ADMIN), 403);
        abort_if($supplierInvoice->status === SupplierInvoice::STATUS_PAID, 422, 'Invoice ini sudah lunas.');

        $validated = $request->validated();
        $paidAmount = (float) $supplierInvoice->paid_amount + (float) $validated['amount_paid_now'];
        $paidAmount = min($paidAmount, (float) $supplierInvoice->amount);

        $supplierInvoice->update([
            'paid_amount' => $paidAmount,
            'status' => $paidAmount >= (float) $supplierInvoice->amount
                ? SupplierInvoice::STATUS_PAID
                : SupplierInvoice::STATUS_PARTIAL,
        ]);

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }
}
