<?php

namespace App\Models\GA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaQuickRequestItem extends Model
{
    protected $fillable = [
        'ga_quick_request_id',
        'item_name',
        'qty',
        'unit',
        'notes',
        'photo_link',
    ];

    public function quickRequest(): BelongsTo
    {
        return $this->belongsTo(GaQuickRequest::class, 'ga_quick_request_id');
    }
}
