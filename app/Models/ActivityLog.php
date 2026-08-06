<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Catatan audit ringan untuk aksi administratif Head — bukan pengganti
 * log aplikasi (storage/logs), khusus utk aksi yang perlu terlihat di UI
 * (mis. "siapa nonaktifkan modul Aset, kapan").
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Helper satu baris supaya controller tidak perlu tahu struktur tabel —
     * cukup panggil ActivityLog::record($user, 'module.disabled', $module, "...").
     */
    public static function record(?User $user, string $action, ?Model $subject, string $description, array $properties = []): self
    {
        return static::create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}
