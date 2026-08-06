<?php

namespace App\Models\SCM;

use App\Models\Approval;
use App\Models\Concerns\Approvable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Batch produksi dibuat Produksi dari MaterialRequest yang sudah approved,
 * disetujui Admin (1 step) sebelum Gudang boleh cetak label & kirim
 * (lihat ProductionBatchItem::labels, DeliveryNoteController).
 */
class ProductionBatch extends Model
{
    use Approvable;

    protected $fillable = [
        'batch_number',
        'material_request_id',
        'produced_by',
        'status',
        'produced_at',
    ];

    protected $casts = [
        'produced_at' => 'datetime',
    ];

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Diajukan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }

    /**
     * Kontrak warna status baku Allez ERP Redesign — sama seperti
     * GaRequest::statusBadgeColor(), supaya konsisten lintas modul.
     */
    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_SUBMITTED => 'bg-status-pending-bg text-status-pending-fg',
            self::STATUS_APPROVED => 'bg-status-approved-bg text-status-approved-fg',
            self::STATUS_REJECTED => 'bg-status-rejected-bg text-status-rejected-fg',
            default => 'bg-status-pending-bg text-status-pending-fg',
        };
    }

    // --- Relasi ---
    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function producedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'produced_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionBatchItem::class);
    }

    // --- Business logic ---

    public static function generateBatchNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $count = static::where('batch_number', 'like', "%/BATCH/{$year}")
                ->lockForUpdate()
                ->count();

            $sequencePadded = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $romanMonth = self::monthToRoman((int) now()->month);

            return "{$sequencePadded}/{$romanMonth}/BATCH/{$year}";
        });
    }

    private static function monthToRoman(int $month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $romans[$month] ?? 'I';
    }

    public function generateApprovalSteps(): void
    {
        if ($this->approvals()->exists()) {
            return;
        }

        $this->approvals()->create([
            'step' => 1,
            'approver_role' => Role::ADMIN,
            'status' => Approval::STATUS_PENDING,
        ]);
    }

    public function syncStatusAfterApproval(): void
    {
        if ($this->hasRejectedStep()) {
            $this->status = self::STATUS_REJECTED;
        } elseif ($this->isFullyApproved()) {
            $this->status = self::STATUS_APPROVED;
        }

        $this->save();
    }

    public function approvalNotificationText(string $decision, ?string $note): string
    {
        $text = "*Batch Produksi {$decision}*\n";
        $text .= "No: {$this->batch_number}\n";
        $text .= 'Dibuat oleh: '.($this->producedBy?->name ?? '-');

        if ($note) {
            $text .= "\nCatatan: {$note}";
        }

        return $text;
    }
}
