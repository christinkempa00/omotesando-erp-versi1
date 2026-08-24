<?php

namespace App\Models\GA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletReportPhoto extends Model
{
    protected $fillable = [
        'outlet_report_id',
        'photo_path',
        'latitude',
        'longitude',
        'taken_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'taken_at' => 'datetime',
    ];

    public function outletReport(): BelongsTo
    {
        return $this->belongsTo(OutletReport::class);
    }
}
