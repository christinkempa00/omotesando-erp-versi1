<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\Concerns\HasBranchLocation;
use App\Models\User;
use Carbon\Carbon;
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
    use HasBranchLocation;

    protected $fillable = [
        'work_date',
        'start_time',
        'end_time',
        'category',
        'branch_id',
        'branch_location_id',
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
    public const CATEGORY_PENDAMPINGAN_VENDOR = 'Pendampingan Vendor';

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_PEMERIKSAAN => self::CATEGORY_PEMERIKSAAN,
            self::CATEGORY_PERBAIKAN => self::CATEGORY_PERBAIKAN,
            self::CATEGORY_INSTALASI => self::CATEGORY_INSTALASI,
            self::CATEGORY_MAINTENANCE => self::CATEGORY_MAINTENANCE,
            self::CATEGORY_PENDAMPINGAN_VENDOR => self::CATEGORY_PENDAMPINGAN_VENDOR,
        ];
    }

    public const TECHNICIAN_BANGKAT = 'Bangkat';
    public const TECHNICIAN_TONI = 'Toni';
    public const TECHNICIAN_WIDI = 'Widi';

    /**
     * Daftar tetap — dipakai sbg pilihan dropdown Teknisi in Charge & sbg
     * sumbu diagram bulat distribusi kerja per teknisi (lihat WorkLogController::index()).
     */
    public static function technicianOptions(): array
    {
        return [
            self::TECHNICIAN_BANGKAT,
            self::TECHNICIAN_TONI,
            self::TECHNICIAN_WIDI,
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

    /**
     * Turunan dari start_time/end_time — tidak disimpan sbg kolom terpisah.
     */
    public function durationMinutes(): ?int
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        return Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time), true);
    }

    public function durationLabel(): ?string
    {
        $minutes = $this->durationMinutes();

        if ($minutes === null) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return match (true) {
            $hours > 0 && $mins > 0 => "{$hours} jam {$mins} menit",
            $hours > 0 => "{$hours} jam",
            default => "{$mins} menit",
        };
    }
}
