<?php

namespace App\Models\Purchasing;

use App\Models\SCM\StockBalance;
use App\Models\SCM\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Baris barang yang benar-benar diterima dari supplier. Untuk PO kategori
 * 'food', baris ini jadi unit stok (stockable) di stock_balances/
 * stock_movements — sejajar dengan App\Models\SCM\BatchLabel, TAPI tanpa
 * proses "batch" produksi (bahan makanan dari supplier diterima langsung,
 * lihat GoodsReceiptItemObserver). Untuk kategori 'general', baris ini
 * murni catatan penerimaan/bukti foto, tidak menyentuh stock_balances sama
 * sekali.
 */
class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'qty_received',
        'expiry_date',
    ];

    protected $casts = [
        'qty_received' => 'integer',
        'expiry_date' => 'date',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function stockBalances(): MorphMany
    {
        return $this->morphMany(StockBalance::class, 'stockable');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'stockable');
    }

    /**
     * Null kalau tidak ada expiry_date (barang umum biasanya tidak punya).
     */
    public function daysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date, false);
    }

    public function isNearExpiry(int $days = 7): bool
    {
        $daysLeft = $this->daysUntilExpiry();

        return $daysLeft !== null && $daysLeft <= $days;
    }
}
