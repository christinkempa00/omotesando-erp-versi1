<?php

namespace App\Models\IT;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItBoard extends Model
{
    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    public function columns(): HasMany
    {
        return $this->hasMany(ItBoardColumn::class, 'board_id')->orderBy('order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
