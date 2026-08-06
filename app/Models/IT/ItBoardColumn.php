<?php

namespace App\Models\IT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItBoardColumn extends Model
{
    protected $fillable = [
        'board_id',
        'name',
        'order',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(ItBoard::class, 'board_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ItTask::class, 'board_column_id')->orderBy('order');
    }
}
