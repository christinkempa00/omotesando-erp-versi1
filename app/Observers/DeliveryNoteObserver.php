<?php

namespace App\Observers;

use App\Models\SCM\DeliveryNote;
use App\Services\SCM\StockLedger;

/**
 * Saat status DeliveryNote berubah jadi "sent" — stok berkurang dari
 * gudang asal (from_branch_id) utk tiap item yang dikirim.
 */
class DeliveryNoteObserver
{
    public function updated(DeliveryNote $deliveryNote): void
    {
        if (! $deliveryNote->wasChanged('status') || $deliveryNote->status !== DeliveryNote::STATUS_SENT) {
            return;
        }

        foreach ($deliveryNote->items()->with('batchLabel')->get() as $item) {
            StockLedger::record($deliveryNote->from_branch_id, $item->batchLabel, -$item->qty_sent, $deliveryNote);
        }
    }
}
