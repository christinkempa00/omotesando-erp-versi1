<?php

namespace App\Models\GA;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * Guard hapus permanen — area cuma boleh dihapus kalau belum pernah
     * dipakai di laporan mana pun. Kolom penghubung foto->area BELUM ada
     * (itu Fase B-2), jadi untuk B-1 selalu false (aman dihapus).
     *
     * TODO (Fase B-2): begitu relasi foto laporan -> area ditambahkan
     * (mis. outlet_report_photos.inspection_area_id), ganti body method
     * ini jadi cek relasi tsb (mis. return $this->reportPhotos()->exists();)
     * — controller sudah memanggil method ini, jadi tidak perlu ubah
     * apa pun di sisi controller saat B-2 dikerjakan.
     */
    public function hasBeenUsed(): bool
    {
        return false;
    }
}
