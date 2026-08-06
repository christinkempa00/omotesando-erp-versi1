<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catatan aktivitas teknisi per outlet — TIDAK terikat ke Asset/MaintenanceJob
 * tertentu (murni log kerja utk kontrol kinerja teknisi, lihat permintaan
 * 03/08/2026), independen dari data Inventaris Aset/Jadwal Pemeliharaan.
 */
class WorkLog extends Model
{
    protected $fillable = [
        'work_date',
        'work_time',
        'category',
        'branch_id',
        'technician_in_charge',
        'technician_assist',
        'work_detail',
        'work_result',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

    public const CATEGORY_PEMERIKSAAN = 'Pemeriksaan';
    public const CATEGORY_PERBAIKAN = 'Perbaikan';
    public const CATEGORY_INSTALASI = 'Instalasi';
    public const CATEGORY_MAINTENANCE = 'Maintenance';

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_PEMERIKSAAN => self::CATEGORY_PEMERIKSAAN,
            self::CATEGORY_PERBAIKAN => self::CATEGORY_PERBAIKAN,
            self::CATEGORY_INSTALASI => self::CATEGORY_INSTALASI,
            self::CATEGORY_MAINTENANCE => self::CATEGORY_MAINTENANCE,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WorkLogAttachment::class);
    }

    /**
     * Dipakai index/show sbg indikator progres — TIDAK ada kolom status
     * terpisah, cukup dihitung dari kosong/tidaknya work_result (diisi
     * belakangan begitu pekerjaan selesai, lihat StoreWorkLogRequest).
     */
    public function isComplete(): bool
    {
        return filled($this->work_result);
    }
}
