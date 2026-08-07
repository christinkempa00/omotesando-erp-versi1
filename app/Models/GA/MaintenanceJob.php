<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MaintenanceJob extends Model
{
    protected $fillable = [
        'job_code',
        'asset_id',
        'title',
        'type',
        'priority',
        'status',
        'scheduled_date',
        'scheduled_time',
        'pic_name',
        'vendor_name',
        'location',
        'branch_id',
        'cost',
        'checklist',
        'notes',
        'completion_notes',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'cost' => 'decimal:2',
        'checklist' => 'array',
        'completed_at' => 'datetime',
    ];

    // --- Status ---
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Terjadwal',
            self::STATUS_IN_PROGRESS => 'Sedang Berjalan',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_SCHEDULED => 'bg-blue-100 text-blue-700',
            self::STATUS_IN_PROGRESS => 'bg-yellow-100 text-yellow-700',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-700',
            self::STATUS_CANCELLED => 'bg-gray-200 text-gray-600',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    // --- Tipe pekerjaan ---
    public static function typeLabels(): array
    {
        return [
            'preventive' => 'Preventive Maintenance',
            'corrective' => 'Corrective Maintenance',
            'cleaning_inspection' => 'Cleaning Inspection',
            'calibration' => 'Calibration',
            'service_vendor' => 'Service Vendor',
            'predictive' => 'Predictive Maintenance',
        ];
    }

    // --- Prioritas ---
    public static function priorityLabels(): array
    {
        return [
            'emergency' => 'Emergency',
            'high' => 'High',
            'normal' => 'Normal',
        ];
    }

    public static function priorityBadgeColor(string $priority): string
    {
        return match ($priority) {
            'emergency' => 'bg-red-100 text-red-700',
            'high' => 'bg-orange-100 text-orange-700',
            'normal' => 'bg-slate-100 text-slate-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    // --- Relasi ---
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // --- Business logic ---

    /**
     * Generate kode pekerjaan: MNT-2026-0001, reset per tahun.
     */
    public static function generateJobCode(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $count = static::lockForUpdate()
                ->whereYear('created_at', $year)
                ->count();

            $next = $count + 1;

            return 'MNT-'.$year.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Apakah pekerjaan sudah lewat tanggal & belum selesai.
     */
    public function isOverdue(): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            return false;
        }

        return $this->scheduled_date !== null
            && $this->scheduled_date->isBefore(Carbon::today());
    }

    /**
     * Progress checklist dalam persen (0-100).
     */
    public function checklistProgress(): int
    {
        $items = $this->checklist ?? [];

        if (count($items) === 0) {
            return $this->status === self::STATUS_COMPLETED ? 100 : 0;
        }

        $done = collect($items)->where('done', true)->count();

        return (int) round($done / count($items) * 100);
    }

    /**
     * Teks notifikasi Telegram GA (add/edit/delete Jadwal Pemeliharaan) —
     * lihat TelegramNotifier & pemanggilnya di MaintenanceJobController.
     */
    public function telegramText(string $action, User $actor): string
    {
        $label = [
            'created' => 'Jadwal Pemeliharaan Baru',
            'updated' => 'Jadwal Pemeliharaan Diperbarui',
            'deleted' => 'Jadwal Pemeliharaan Dihapus',
        ][$action];

        return "*{$label}*\n"
            ."Kode: {$this->job_code}\n"
            ."Pekerjaan: {$this->title}\n"
            .'Aset: '.($this->asset?->name ?: '-')."\n"
            .'Oleh: '.$actor->name;
    }
}
