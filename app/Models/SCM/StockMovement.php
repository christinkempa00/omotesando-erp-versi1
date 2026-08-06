<?php

namespace App\Models\SCM;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Log histori tiap perubahan stok (insert-only) — dibuat OTOMATIS oleh
 * App\Services\SCM\StockLedger, bukan form manual. Beda dengan
 * StockBalance (saldo akhir), tabel ini menyimpan SETIAP perubahan supaya
 * bisa ditelusuri asal-usulnya (BatchLabel = produksi masuk, DeliveryNote
 * = keluar saat kirim, DeliveryReceipt = masuk di outlet, dst).
 */
class StockMovement extends Model
{
    /**
     * Log insert-only — tidak pernah di-update setelah dibuat, jadi
     * sengaja tidak ada kolom updated_at (lihat migration).
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'branch_id',
        'stockable_type',
        'stockable_id',
        'qty_change',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'qty_change' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Polymorphic (Fase 2) — BatchLabel atau
     * App\Models\Purchasing\GoodsReceiptItem. Lihat StockBalance::stockable().
     */
    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
