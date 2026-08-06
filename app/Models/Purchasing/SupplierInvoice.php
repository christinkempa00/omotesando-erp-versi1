<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tagihan/invoice supplier terkait satu GoodsReceipt — dikelola Finance
 * (input invoice, catat pembayaran). Terpisah dari alur approval PO, karena
 * invoice baru ada SETELAH barang benar-benar diterima.
 */
class SupplierInvoice extends Model
{
    protected $fillable = [
        'goods_receipt_id',
        'invoice_number',
        'amount',
        'due_date',
        'status',
        'paid_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_amount' => 'decimal:2',
    ];

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_UNPAID => 'Belum Dibayar',
            self::STATUS_PARTIAL => 'Dibayar Sebagian',
            self::STATUS_PAID => 'Lunas',
        ];
    }

    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_UNPAID => 'bg-status-pending-bg text-status-pending-fg',
            self::STATUS_PARTIAL => 'bg-status-discrepancy-bg text-status-discrepancy-fg',
            self::STATUS_PAID => 'bg-status-approved-bg text-status-approved-fg',
            default => 'bg-status-pending-bg text-status-pending-fg',
        };
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }
}
