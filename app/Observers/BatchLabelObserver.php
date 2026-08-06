<?php

namespace App\Observers;

use App\Models\SCM\BatchLabel;
use App\Services\SCM\StockLedger;

/**
 * Batch produksi "selesai & diterima gudang" direpresentasikan sebagai
 * momen Gudang mencetak label utk sebuah ProductionBatchItem (lihat
 * BatchLabelController::store) — itu titik paling dapat diandalkan utk tahu
 * branch_id gudang (dari user yang mencetak), karena ProductionBatch sendiri
 * tidak py branch_id. Stok masuk ke branch milik user yang mencetak label.
 */
class BatchLabelObserver
{
    public function created(BatchLabel $label): void
    {
        $branchId = $label->printedBy?->branch_id;

        // Admin yang belum terikat ke satu branch tidak bisa dicatat stoknya
        // masuk ke mana — dilewati (bukan error) drpd salah catat lokasi.
        if (! $branchId) {
            return;
        }

        $label->loadMissing('productionBatchItem');

        StockLedger::record($branchId, $label, $label->productionBatchItem->qty, $label);
    }
}
