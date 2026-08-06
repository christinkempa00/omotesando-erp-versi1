<?php

namespace App\Models\IT;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItTaskComment extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'content',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ItTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
