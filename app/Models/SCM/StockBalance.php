<?php

namespace App\Models\SCM;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Saldo stok real-time per branch x unit stok (stockable) — SATU-SATUNYA cara
 * baris ini berubah adalah lewat App\Services\SCM\StockLedger (dipanggil dari
 * observer, bukan controller langsung). Jangan update qty_on_hand manual di
 * tempat lain, nanti stock_movements (log histori) jadi tidak sinkron.
 *
 * stockable polymorphic (Fase 2) — bisa BatchLabel (unit hasil produksi
 * ber-QR) ATAU App\Models\Purchasing\GoodsReceiptItem (bahan makanan yang
 * dibeli langsung dari supplier, tidak lewat proses "batch"). Awalnya
 * (Fase 1) kolom ini FK khusus batch_label_id — digeneralisasi supaya kedua
 * sumber stok berbagi satu ledger yang sama.
 */
class StockBalance extends Model
{
    protected $fillable = [
        'branch_id',
        'stockable_type',
        'stockable_id',
        'qty_on_hand',
    ];

    protected $casts = [
        'qty_on_hand' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }
}
