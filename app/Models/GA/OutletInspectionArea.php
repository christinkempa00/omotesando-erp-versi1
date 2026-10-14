<?php

namespace App\Models\GA;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Area pemeriksaan per outlet (mis. Dapur, Toilet, Area Makan) — dikelola GA
 * (Fase B-1), jadi acuan checklist form laporan foto outlet (Fase B-2,
 * belum dibangun).
 */
class OutletInspectionArea extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reportPhotos(): HasMany
    {
        return $this->hasMany(OutletReportPhoto::class, 'inspection_area_id');
    }

    /**
     * Guard hapus permanen — area cuma boleh dihapus kalau belum pernah
     * dipakai di laporan mana pun (Fase B-2: cek relasi foto laporan).
     */
    public function hasBeenUsed(): bool
    {
        return $this->reportPhotos()->exists();
    }
}
