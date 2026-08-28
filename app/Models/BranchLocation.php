<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Sub-lokasi fisik opsional di dalam satu outlet (mis. "Ask for Patty"
 * punya beberapa titik lokasi). Kebanyakan outlet tidak punya baris di
 * sini sama sekali — form-form GA menyembunyikan field "Cabang" total
 * kalau outlet yang dipilih tidak punya sub-lokasi terdaftar.
 */
class BranchLocation extends Model
{
    protected $fillable = ['branch_id', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Bentuk {branch_id: [{id, name}]} — dikirim ke form GA sbg JSON supaya
     * dropdown "Cabang" bisa muncul/terisi reaktif begitu outlet dipilih,
     * tanpa reload halaman. Pola sama persis roleModuleDefaults di
     * UserManagementController::formData().
     */
    public static function groupedByBranch(): Collection
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('branch_id')
            ->map(fn (Collection $rows) => $rows->map(fn (self $l) => ['id' => $l->id, 'name' => $l->name])->values());
    }
}
