<?php

namespace App\Models\GA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniformRecordItem extends Model
{
    protected $fillable = [
        'uniform_record_id',
        'uniform_stock_id',
        'uniform_type',
        'size',
        'color',
        'qty',
        'item_condition',
        'item_notes',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function uniformRecord(): BelongsTo
    {
        return $this->belongsTo(UniformRecord::class);
    }

    public function uniformStock(): BelongsTo
    {
        return $this->belongsTo(UniformStock::class);
    }

    /**
     * "Ukuran / Warna" digabung jadi satu kolom "Spesifikasi" di tampilan & PDF.
     */
    public function specificationLabel(): string
    {
        return collect([$this->size, $this->color])->filter()->implode(' / ') ?: '-';
    }
}
