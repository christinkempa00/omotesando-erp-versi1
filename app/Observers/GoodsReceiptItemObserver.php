<?php

namespace App\Observers;

use App\Models\Finance\JournalEntry;
use App\Models\Finance\TransactionAccountMapping;
use App\Models\Purchasing\GoodsReceiptItem;
use App\Models\Purchasing\PurchaseOrder;
use App\Services\Finance\JournalPoster;
use App\Services\SCM\StockLedger;

/**
 * Stok bertambah OTOMATIS di branch tujuan PO — TAPI hanya utk PO kategori
 * 'food' (bahan makanan/kebutuhan dapur). Barang kategori 'general' tidak
 * pakai "batch" ala produksi sama sekali (klarifikasi user, lihat Fase 2) —
 * baris GoodsReceiptItem-nya tetap tersimpan sbg bukti penerimaan, tapi
 * TIDAK menyentuh stock_balances/stock_movements.
 *
 * Fase 3 Finance — jurnal Persediaan/Hutang Usaha JUGA di-post dari sini
 * (bukan dari observer terpisah di model GoodsReceipt) karena
 * GoodsReceiptController::store() membuat baris GoodsReceipt (header) DULU
 * baru item-nya belakangan dalam transaksi yang sama — observer "created"
 * di GoodsReceipt sendiri akan selalu melihat items() kosong. Item TERAKHIR
 * yang tersimpan (dicek dgn membandingkan jumlah item vs jumlah item PO)
 * yang memicu posting jurnal, sekali per GoodsReceipt.
 */
class GoodsReceiptItemObserver
{
    public function created(GoodsReceiptItem $item): void
    {
        $item->loadMissing('goodsReceipt.purchaseOrder.items', 'goodsReceipt.items.purchaseOrderItem');
        $goodsReceipt = $item->goodsReceipt;
        $purchaseOrder = $goodsReceipt->purchaseOrder;

        if ($purchaseOrder->category === PurchaseOrder::CATEGORY_FOOD) {
            StockLedger::record($purchaseOrder->branch_id, $item, $item->qty_received, $goodsReceipt);
        }

        $this->postJournalIfComplete($goodsReceipt, $purchaseOrder);
    }

    /**
     * Semua item PO ini sudah punya GoodsReceiptItem-nya masing-masing →
     * ini item terakhir yang disimpan → post 1 jurnal utk keseluruhan
     * penerimaan. Guard JournalEntry::where(...)->doesntExist() mencegah
     * dobel post kalau observer ini somehow terpanggil lebih dari sekali
     * utk kondisi "lengkap" yang sama.
     */
    private function postJournalIfComplete($goodsReceipt, PurchaseOrder $purchaseOrder): void
    {
        if ($goodsReceipt->items()->count() < $purchaseOrder->items()->count()) {
            return;
        }

        $alreadyPosted = JournalEntry::where('reference_type', $goodsReceipt::class)
            ->where('reference_id', $goodsReceipt->id)
            ->exists();

        if ($alreadyPosted) {
            return;
        }

        $goodsReceipt->loadMissing('items.purchaseOrderItem');
        $amount = $goodsReceipt->items->sum(
            fn ($i) => $i->qty_received * (float) $i->purchaseOrderItem->unit_price
        );

        if ($amount <= 0) {
            return;
        }

        JournalPoster::post(
            TransactionAccountMapping::TYPE_GOODS_RECEIPT_CREATED,
            $amount,
            $goodsReceipt,
            "Penerimaan barang PO {$purchaseOrder->po_number}"
        );
    }
}
