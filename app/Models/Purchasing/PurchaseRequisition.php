<?php

namespace App\Models\Purchasing;

use App\Models\Approval;
use App\Models\Branch;
use App\Models\Concerns\Approvable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Purchase Requisition — diajukan Outlet utk bahan makanan (cuma nama
 * barang & qty, tanpa harga/supplier), disetujui/ditolak Purchasing (1
 * step). Kalau approved, Purchasing bikin PurchaseOrder dari sini (pilih
 * supplier + isi harga) — lihat PurchaseOrderController::createFromRequisition.
 * Pola sama persis dengan MaterialRequest -> ProductionBatch di modul SCM.
 */
class PurchaseRequisition extends Model
{
    use Approvable;

    protected $fillable = [
        'requisition_number',
        'branch_id',
        'requested_by',
        'status',
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
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    // --- Business logic ---

    /**
     * Format: 000X/[bulan romawi]/PR/[tahun] — pola sama dgn dokumen lain.
     */
    public static function generateRequisitionNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $count = static::where('requisition_number', 'like', "%/PR/{$year}")
                ->lockForUpdate()
                ->count();

            $sequencePadded = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $romanMonth = self::monthToRoman((int) now()->month);

            return "{$sequencePadded}/{$romanMonth}/PR/{$year}";
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
     * Satu step saja: Purchasing. Beda dengan PurchaseOrder yang bisa 2
     * atau 3 step tergantung kategori (lihat PurchaseOrder::generateApprovalSteps).
     */
    public function generateApprovalSteps(): void
    {
        if ($this->approvals()->exists()) {
            return;
        }

        $this->approvals()->create([
            'step' => 1,
            'approver_role' => Role::PURCHASING,
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
        $text = "*Purchase Requisition {$decision}*\n";
        $text .= "No: {$this->requisition_number}\n";
        $text .= 'Outlet: '.($this->branch?->name ?? '-');

        if ($note) {
            $text .= "\nCatatan: {$note}";
        }

        return $text;
    }
}
