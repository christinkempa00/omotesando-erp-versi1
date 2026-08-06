<?php

namespace App\Models\SCM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dibuat OTOMATIS oleh DeliveryNoteItemObserver saat qty_received sebuah
 * DeliveryNoteItem berbeda dari qty_sent — bukan form manual.
 */
class DiscrepancyReport extends Model
{
    protected $fillable = [
        'delivery_note_id',
        'delivery_note_item_id',
        'qty_expected',
        'qty_received',
        'qty_diff',
        'reason',
    ];

    protected $casts = [
        'qty_expected' => 'integer',
        'qty_received' => 'integer',
        'qty_diff' => 'integer',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function deliveryNoteItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryNoteItem::class);
    }
}
