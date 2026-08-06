<?php

namespace App\Models\GA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaRequestAttachment extends Model
{
    protected $fillable = [
        'ga_request_id',
        'photo_path',
    ];

    public function gaRequest(): BelongsTo
    {
        return $this->belongsTo(GaRequest::class);
    }
}
