<?php

namespace App\Observers;

use App\Models\SCM\DeliveryReceipt;
use App\Services\SCM\StockLedger;

/**
 * Saat DeliveryReceipt tersimpan — stok bertambah di outlet tujuan
 * (to_branch_id) sebesar qty_received tiap item (BUKAN qty_sent — kalau ada
 * selisih, yang masuk ke stok outlet adalah jumlah fisik yang benar-benar
 * diterima). Penting: DeliveryReceiptController::store() SENGAJA
 * menyimpan qty_received ke tiap DeliveryNoteItem dulu sebelum membuat baris
 * DeliveryReceipt ini, supaya saat observer ini jalan datanya sudah lengkap.
 */
class DeliveryReceiptObserver
{
    public function created(DeliveryReceipt $receipt): void
    {
        $deliveryNote = $receipt->deliveryNote;

        foreach ($deliveryNote->items()->with('batchLabel')->get() as $item) {
            if ($item->qty_received === null) {
                continue;
            }

            StockLedger::record($deliveryNote->to_branch_id, $item->batchLabel, $item->qty_received, $receipt);
        }
    }
}
