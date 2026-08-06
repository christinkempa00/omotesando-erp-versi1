<?php

namespace App\Models\SCM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryNoteItem extends Model
{
    protected $fillable = [
        'delivery_note_id',
        'batch_label_id',
        'qty_sent',
        'qty_received',
        'unit_price',
    ];

    protected $casts = [
        'qty_sent' => 'integer',
        'qty_received' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function batchLabel(): BelongsTo
    {
        return $this->belongsTo(BatchLabel::class);
    }

    public function discrepancy(): HasOne
    {
        return $this->hasOne(DiscrepancyReport::class);
    }
}
