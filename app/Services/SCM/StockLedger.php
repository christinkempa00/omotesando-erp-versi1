<?php

namespace App\Services\SCM;

use App\Models\SCM\StockBalance;
use App\Models\SCM\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * SATU-SATUNYA tempat stock_balances boleh berubah — dipanggil dari
 * observer (BatchLabelObserver, DeliveryNoteObserver, DeliveryReceiptObserver,
 * GoodsReceiptItemObserver), bukan langsung dari controller. Setiap
 * pemanggilan mengubah saldo DAN mencatat histori (stock_movements) dalam
 * satu transaksi, supaya dua-duanya selalu sinkron.
 */
class StockLedger
{
    /**
     * @param  Model  $stockable  Unit stok yang saldonya berubah — BatchLabel
     *                            (hasil produksi ber-QR) atau
     *                            App\Models\Purchasing\GoodsReceiptItem
     *                            (bahan makanan dari supplier). Dicatat
     *                            polymorphic (stockable_type/stockable_id)
     *                            supaya kedua sumber berbagi satu ledger.
     * @param  Model  $reference  Model yang memicu perubahan ini (BatchLabel,
     *                            DeliveryNote, DeliveryReceipt, GoodsReceipt,
     *                            dst) — dicatat sebagai reference_type/
     *                            reference_id supaya tiap baris stock_movements
     *                            bisa ditelusuri asalnya.
     */
    public static function record(int $branchId, Model $stockable, int $qtyChange, Model $reference): StockBalance
    {
        return DB::transaction(function () use ($branchId, $stockable, $qtyChange, $reference) {
            $balance = StockBalance::query()
                ->where('branch_id', $branchId)
                ->where('stockable_type', $stockable::class)
                ->where('stockable_id', $stockable->getKey())
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = StockBalance::create([
                    'branch_id' => $branchId,
                    'stockable_type' => $stockable::class,
                    'stockable_id' => $stockable->getKey(),
                    'qty_on_hand' => 0,
                ]);
            }

            $balance->qty_on_hand += $qtyChange;
            $balance->save();

            StockMovement::create([
                'branch_id' => $branchId,
                'stockable_type' => $stockable::class,
                'stockable_id' => $stockable->getKey(),
                'qty_change' => $qtyChange,
                'reference_type' => $reference::class,
                'reference_id' => $reference->getKey(),
            ]);

            return $balance;
        });
    }
}
