<?php

namespace App\Models\GA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaRequestItem extends Model
{
    protected $fillable = [
        'ga_request_id',
        'item_name',
        'type',
        'unit',
        'qty',
        'price_per_unit',
        'total',
        'vendor_name',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Total selalu dihitung otomatis dari qty * price_per_unit,
        // supaya tidak mungkin ada input total yang tidak konsisten.
        static::saving(function (GaRequestItem $item) {
            $item->total = $item->qty * $item->price_per_unit;
        });

        static::saved(function (GaRequestItem $item) {
            $item->gaRequest?->recalculateTotal();
        });

        static::deleted(function (GaRequestItem $item) {
            $item->gaRequest?->recalculateTotal();
        });
    }

    public function gaRequest(): BelongsTo
    {
        return $this->belongsTo(GaRequest::class);
    }
}
