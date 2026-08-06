<?php

namespace App\Models\SCM;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * Surat jalan pengiriman batch dari gudang/central kitchen ke outlet tujuan.
 * Alur status: draft -> sent (wajib foto, generate QR) -> received /
 * received_with_discrepancy (otomatis, lihat DeliveryNoteItemObserver).
 */
class DeliveryNote extends Model
{
    protected $fillable = [
        'delivery_code',
        'from_branch_id',
        'to_branch_id',
        'sent_by',
        'sent_photo_path',
        'sent_at',
        'status',
        'qr_code',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_RECEIVED_WITH_DISCREPANCY = 'received_with_discrepancy';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SENT => 'Dikirim',
            self::STATUS_RECEIVED => 'Diterima',
            self::STATUS_RECEIVED_WITH_DISCREPANCY => 'Diterima (Selisih)',
        ];
    }

    /**
     * Kontrak warna status baku Allez ERP Redesign. "draft" tetap netral
     * (belum masuk alur), "sent" masuk tone pending (menunggu tindakan
     * Outlet), "received_with_discrepancy" pakai tone discrepancy (ungu) —
     * beda dari GaRequest/MaterialRequest yang cuma py 3-4 status.
     */
    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-700',
            self::STATUS_SENT => 'bg-status-pending-bg text-status-pending-fg',
            self::STATUS_RECEIVED => 'bg-status-received-bg text-status-received-fg',
            self::STATUS_RECEIVED_WITH_DISCREPANCY => 'bg-status-discrepancy-bg text-status-discrepancy-fg',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    // --- Relasi ---
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(DeliveryReceipt::class);
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(DiscrepancyReport::class);
    }

    // --- Business logic ---

    /**
     * Format: 000X/[bulan romawi]/SJ/[tahun] — pola sama dgn dokumen lain.
     */
    public static function generateDeliveryCode(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $count = static::where('delivery_code', 'like', "%/SJ/{$year}")
                ->lockForUpdate()
                ->count();

            $sequencePadded = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $romanMonth = self::monthToRoman((int) now()->month);

            return "{$sequencePadded}/{$romanMonth}/SJ/{$year}";
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
}
