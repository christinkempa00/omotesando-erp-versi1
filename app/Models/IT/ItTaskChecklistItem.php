<?php

namespace App\Models\IT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItTaskChecklistItem extends Model
{
    protected $fillable = [
        'task_id',
        'content',
        'is_done',
        'order',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'order' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ItTask::class, 'task_id');
    }
}
