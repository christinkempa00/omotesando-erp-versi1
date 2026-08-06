<?php

namespace App\Models\SCM;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * Label QR fisik per produk hasil batch (ProductionBatchItem) — dicetak
 * Gudang, lalu dipakai sebagai referensi qty dikirim/diterima di
 * DeliveryNoteItem. Boleh dicetak ulang (baris baru, label_code beda).
 */
class BatchLabel extends Model
{
    protected $fillable = [
        'production_batch_item_id',
        'label_code',
        'qr_code',
        'expiry_date',
        'printed_at',
        'printed_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'printed_at' => 'datetime',
    ];

    public function productionBatchItem(): BelongsTo
    {
        return $this->belongsTo(ProductionBatchItem::class);
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
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
     * Null kalau tidak ada expiry_date (tidak semua produk butuh ED).
     */
    public function daysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date, false);
    }

    /**
     * Dipakai FEFO/laporan near-expiry — highlight visual di UI kalau true.
     */
    public function isNearExpiry(int $days = 7): bool
    {
        $daysLeft = $this->daysUntilExpiry();

        return $daysLeft !== null && $daysLeft <= $days;
    }

    /**
     * Kode label unik: [nomor batch tanpa "/"]-[id item]-[urutan cetak
     * ulang utk item ini]. Dibungkus transaksi + lock spy aman dari race
     * condition, sama seperti pola generateRequestNumber() di modul lain.
     */
    public static function generateLabelCode(ProductionBatchItem $item): string
    {
        return DB::transaction(function () use ($item) {
            $count = static::where('production_batch_item_id', $item->id)
                ->lockForUpdate()
                ->count();

            $batchPart = str_replace('/', '', $item->productionBatch->batch_number);

            return "{$batchPart}-{$item->id}-".($count + 1);
        });
    }
}
