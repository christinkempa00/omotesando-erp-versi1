<?php

namespace App\Observers;

use App\Models\SCM\DeliveryNoteItem;
use App\Models\SCM\DiscrepancyReport;

/**
 * Auto-generate Laporan Selisih — dipicu tiap kali qty_received sebuah
 * DeliveryNoteItem diisi/diubah dan hasilnya beda dari qty_sent. Ini SATU-
 * SATUNYA tempat discrepancy_reports dibuat (bukan form manual), sesuai
 * permintaan "sistem otomatis generate".
 */
class DeliveryNoteItemObserver
{
    public function updated(DeliveryNoteItem $item): void
    {
        if (! $item->wasChanged('qty_received') || $item->qty_received === null) {
            return;
        }

        if ($item->qty_received === $item->qty_sent) {
            return;
        }

        // Hindari duplikat kalau observer ke-trigger lagi utk item yang sama
        // (mis. update lain di baris yang sama setelah discrepancy sudah ada).
        if (DiscrepancyReport::where('delivery_note_item_id', $item->id)->exists()) {
            return;
        }

        DiscrepancyReport::create([
            'delivery_note_id' => $item->delivery_note_id,
            'delivery_note_item_id' => $item->id,
            'qty_expected' => $item->qty_sent,
            'qty_received' => $item->qty_received,
            'qty_diff' => $item->qty_received - $item->qty_sent,
        ]);
    }
}
