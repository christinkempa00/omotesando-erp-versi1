<?php

namespace App\Models\SCM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionBatchItem extends Model
{
    protected $fillable = [
        'production_batch_id',
        'item_name',
        'qty',
        'unit',
        'unit_cost',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function productionBatch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(BatchLabel::class);
    }
}
