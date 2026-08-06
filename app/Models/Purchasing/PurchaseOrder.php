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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * Purchase Order ke supplier. Dua jalur berbeda tergantung category:
 * - 'food' (bahan makanan): HARUS berasal dari PurchaseRequisition yang
 *   sudah disetujui Purchasing (lihat PurchaseRequisition) — Purchasing yang
 *   membuat PO ini (pilih supplier + isi harga). Karena Purchasing sudah
 *   "menyetujui" lewat requisisi, PO-nya sendiri cuma perlu 2 step approval:
 *   Head lalu Finance ("pencairan dana").
 * - 'general' (barang umum): dibuat LANGSUNG oleh GA (tanpa requisisi
 *   sebelumnya, purchase_requisition_id null), tapi karena belum pernah
 *   direview Purchasing, PO-nya sendiri butuh 3 step: Purchasing dulu, baru
 *   Head, baru Finance.
 * Setelah GoodsReceipt tercatat, status jadi "received". category
 * menentukan juga apakah GoodsReceiptItem-nya ikut memperbarui
 * stock_balances — lihat GoodsReceiptItemObserver.
 */
class PurchaseOrder extends Model
{
    use Approvable;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'purchase_requisition_id',
        'branch_id',
        'ordered_by',
        'category',
        'order_date',
        'status',
    ];

    protected $casts = [
        'order_date' => 'date',
    ];

    public const CATEGORY_FOOD = 'food';
    public const CATEGORY_GENERAL = 'general';

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RECEIVED = 'received';

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_FOOD => 'Bahan Makanan (dari Purchase Requisition)',
            self::CATEGORY_GENERAL => 'Barang Umum (GA)',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Diajukan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_RECEIVED => 'Diterima',
        ];
    }

    /**
     * Kontrak warna status baku Allez ERP Redesign — sama seperti model lain.
     */
    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_SUBMITTED => 'bg-status-pending-bg text-status-pending-fg',
            self::STATUS_APPROVED => 'bg-status-approved-bg text-status-approved-fg',
            self::STATUS_REJECTED => 'bg-status-rejected-bg text-status-rejected-fg',
            self::STATUS_RECEIVED => 'bg-status-received-bg text-status-received-fg',
            default => 'bg-status-pending-bg text-status-pending-fg',
        };
    }

    // --- Relasi ---
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipt(): HasOne
    {
        return $this->hasOne(GoodsReceipt::class);
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    // --- Business logic ---

    /**
     * Format: 000X/[bulan romawi]/PO/[tahun] — pola sama persis dengan
     * GaRequest::generateRequestNumber().
     */
    public static function generatePoNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $count = static::where('po_number', 'like', "%/PO/{$year}")
                ->lockForUpdate()
                ->count();

            $sequencePadded = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $romanMonth = self::monthToRoman((int) now()->month);

            return "{$sequencePadded}/{$romanMonth}/PO/{$year}";
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
     * category 'food': 2 step (Head lalu Finance) — Purchasing sudah
     * "menyetujui" barang ini lewat approve PurchaseRequisition sebelum PO
     * ini dibuat, jadi tidak perlu step Purchasing lagi di sini.
     * category 'general': 3 step (Purchasing dulu, baru Head, baru Finance)
     * — PO ini dibuat langsung oleh GA tanpa requisisi sebelumnya, jadi
     * Purchasing baru meninjau di titik ini.
     */
    public function generateApprovalSteps(): void
    {
        if ($this->approvals()->exists()) {
            return;
        }

        $steps = $this->category === self::CATEGORY_GENERAL
            ? [
                ['step' => 1, 'role' => Role::PURCHASING],
                ['step' => 2, 'role' => Role::HEAD],
                ['step' => 3, 'role' => Role::FINANCE],
            ]
            : [
                ['step' => 1, 'role' => Role::HEAD],
                ['step' => 2, 'role' => Role::FINANCE],
            ];

        foreach ($steps as $step) {
            $this->approvals()->create([
                'step' => $step['step'],
                'approver_role' => $step['role'],
                'status' => Approval::STATUS_PENDING,
            ]);
        }
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
        $text = "*Purchase Order {$decision}*\n";
        $text .= "No: {$this->po_number}\n";
        $text .= 'Supplier: '.($this->supplier?->name ?? '-')."\n";
        $text .= 'Tujuan: '.($this->branch?->name ?? '-');

        if ($note) {
            $text .= "\nCatatan: {$note}";
        }

        return $text;
    }
}
