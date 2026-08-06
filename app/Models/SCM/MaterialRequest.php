<?php

namespace App\Models\SCM;

use App\Models\Approval;
use App\Models\Branch;
use App\Models\Concerns\Approvable;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Pengajuan bahan baku oleh role Produksi — disetujui/ditolak Admin (1 step,
 * beda dengan GaRequest yang 3 step Finance/Head/Finance). Sekali approved,
 * bisa dipakai Produksi utk bikin ProductionBatch (lihat generateApprovalSteps
 * di sana & ProductionBatchController::store).
 */
class MaterialRequest extends Model
{
    use Approvable;

    protected $fillable = [
        'request_number',
        'division_id',
        'branch_id',
        'requested_by',
        'status',
        'description',
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
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    // --- Business logic ---

    /**
     * Format: 000X/[bulan romawi]/PB/[tahun] — pola sama persis dengan
     * GaRequest::generateRequestNumber(), cuma beda kode dokumen (PB).
     */
    public static function generateRequestNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $count = static::where('request_number', 'like', "%/PB/{$year}")
                ->lockForUpdate()
                ->count();

            $sequencePadded = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $romanMonth = self::monthToRoman((int) now()->month);

            return "{$sequencePadded}/{$romanMonth}/PB/{$year}";
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

    /**
     * Satu step approval saja: Admin. Beda dengan GaRequest yang 3 step.
     */
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
        $text = "*Pengajuan Bahan {$decision}*\n";
        $text .= "No: {$this->request_number}\n";
        $text .= 'Pemohon: '.($this->requestedBy?->name ?? '-')."\n";
        $text .= 'Outlet: '.($this->branch?->name ?? '-');

        if ($note) {
            $text .= "\nCatatan: {$note}";
        }

        return $text;
    }
}
