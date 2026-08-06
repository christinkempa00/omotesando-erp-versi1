<?php

namespace App\Models\IT;

use App\Models\SystemModule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItTask extends Model
{
    protected $fillable = [
        'board_column_id',
        'title',
        'description',
        'priority',
        'assignee_id',
        'due_date',
        'order',
        'related_module_id',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'order' => 'integer',
    ];

    // --- Konstanta prioritas ---
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public static function priorityLabels(): array
    {
        return [
            self::PRIORITY_LOW => 'Rendah',
            self::PRIORITY_MEDIUM => 'Sedang',
            self::PRIORITY_HIGH => 'Tinggi',
            self::PRIORITY_URGENT => 'Mendesak',
        ];
    }

    public static function priorityBadgeColor(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_LOW => 'bg-slate-100 text-slate-600',
            self::PRIORITY_MEDIUM => 'bg-blue-100 text-blue-700',
            self::PRIORITY_HIGH => 'bg-orange-100 text-orange-700',
            self::PRIORITY_URGENT => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(ItBoardColumn::class, 'board_column_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function relatedModule(): BelongsTo
    {
        return $this->belongsTo(SystemModule::class, 'related_module_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(ItTaskLabel::class, 'it_task_label', 'task_id', 'label_id');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ItTaskChecklistItem::class, 'task_id')->orderBy('order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ItTaskComment::class, 'task_id')->oldest();
    }
}
