<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StoreGoodsReceiptRequest;
use App\Models\Purchasing\PurchaseOrder;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Konfirmasi barang diterima dari supplier — foto WAJIB (lihat
 * StoreGoodsReceiptRequest). Gudang menerima kalau PO tujuannya branch
 * miliknya (bisa kategori 'food' MAUPUN 'general' — Central Storage juga
 * dikelola Gudang). GA menerima kalau kategori 'general' (tidak
 * branch-scoped, lihat PurchaseOrderController::authorizeView). Sengaja
 * TIDAK ada Cost Control di sini — bahan makanan selalu masuk lewat
 * Gudang/Central Kitchen (lihat klarifikasi alur PO Fase 2).
 */
class GoodsReceiptController extends Controller
{
    public function store(StoreGoodsReceiptRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::GA, Role::ADMIN), 403);

        if ($user->hasRole(Role::GUDANG) && ! $user->hasRole(Role::ADMIN)) {
            abort_unless($purchaseOrder->branch_id === $user->branch_id, 403);
        } elseif ($user->hasRole(Role::GA) && ! $user->hasRole(Role::ADMIN, Role::GUDANG)) {
            abort_unless($purchaseOrder->category === PurchaseOrder::CATEGORY_GENERAL, 403);
        }

        abort_unless(
            $purchaseOrder->status === PurchaseOrder::STATUS_APPROVED,
            422,
            'PO ini belum disetujui atau barangnya sudah dikonfirmasi diterima sebelumnya.'
        );

        $validated = $request->validated();
        $photoPath = $request->file('photo')->store('purchasing/goods-receipts', 'public');

        DB::transaction(function () use ($validated, $user, $purchaseOrder, $photoPath) {
            $receipt = $purchaseOrder->goodsReceipt()->create([
                'received_by' => $user->id,
                'received_at' => now(),
                'photo_path' => $photoPath,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemInput) {
                $receipt->items()->create([
                    'purchase_order_item_id' => $itemInput['purchase_order_item_id'],
                    'qty_received' => $itemInput['qty_received'],
                    'expiry_date' => $itemInput['expiry_date'] ?? null,
                ]);
            }

            $purchaseOrder->update(['status' => PurchaseOrder::STATUS_RECEIVED]);
        });

        return redirect()
            ->route('purchasing.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Penerimaan barang berhasil dikonfirmasi.');
    }
}
