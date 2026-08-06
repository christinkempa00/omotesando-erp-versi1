<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * Header jurnal — dibuat OTOMATIS lewat App\Services\Finance\JournalPoster
 * (dipanggil dari Observer), bukan form manual. reference (morphTo)
 * menunjuk ke model yang memicu jurnal ini (GaRequest, GoodsReceipt,
 * SupplierInvoice) — pola sama dgn Approval::approvable & StockMovement::reference.
 */
class JournalEntry extends Model
{
    protected $fillable = [
        'entry_number',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Format: 000X/[bulan romawi]/JE/[tahun] — pola sama persis dengan
     * GaRequest::generateRequestNumber().
     */
    public static function generateEntryNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $count = static::where('entry_number', 'like', "%/JE/{$year}")
                ->lockForUpdate()
                ->count();

            $sequencePadded = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $romanMonth = self::monthToRoman((int) now()->month);

            return "{$sequencePadded}/{$romanMonth}/JE/{$year}";
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
