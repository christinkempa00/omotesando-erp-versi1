<?php

namespace App\Models\GA;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class UniformMovement extends Model
{
    protected $fillable = [
        'movement_code',
        'uniform_stock_id',
        'movement_type',
        'quantity',
        'available_after',
        'unusable_after',
        'source_record_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'available_after' => 'integer',
        'unusable_after' => 'integer',
    ];

    // --- Tipe movement ---
    public const TYPE_RESTOCK = 'restock';
    public const TYPE_ISSUE = 'issue';
    public const TYPE_RETURN = 'return';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_DISPOSAL = 'disposal';

    public static function typeLabels(): array
    {
        return [
            self::TYPE_RESTOCK => 'Restock',
            self::TYPE_ISSUE => 'Issue',
            self::TYPE_RETURN => 'Return',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_DISPOSAL => 'Disposal',
        ];
    }

    public static function typeBadgeColor(string $type): string
    {
        return match ($type) {
            self::TYPE_RESTOCK => 'bg-green-100 text-green-700',
            self::TYPE_ISSUE => 'bg-orange-100 text-orange-700',
            self::TYPE_RETURN => 'bg-blue-100 text-blue-700',
            self::TYPE_ADJUSTMENT => 'bg-slate-100 text-slate-700',
            self::TYPE_DISPOSAL => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    // --- Relasi ---
    public function uniformStock(): BelongsTo
    {
        return $this->belongsTo(UniformStock::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // --- Business logic ---

    /**
     * Generate kode movement: MOV-2026-0001, reset per tahun.
     *
     * Dihitung dari NOMOR TERBESAR yang sudah dipakai (bukan COUNT baris) —
     * COUNT rentan macet permanen kalau ada gap di penomoran (mis. satu baris
     * gagal/dibatalkan di tengah, atau baris lama terhapus): begitu
     * count()+1 sudah pernah dipakai, count() tidak akan pernah berubah
     * sendiri, jadi setiap percobaan berikutnya akan terus tabrakan dengan
     * kode yang sama selamanya. Ambil nomor tertinggi yang benar-benar ada
     * lalu +1 supaya otomatis lompat dari gap semacam ini.
     */
    public static function generateMovementCode(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $prefix = "MOV-{$year}-";

            $maxSeq = static::lockForUpdate()
                ->whereYear('created_at', $year)
                ->where('movement_code', 'like', $prefix.'%')
                ->get(['movement_code'])
                ->map(fn (self $m) => (int) substr($m->movement_code, strlen($prefix)))
                ->max() ?? 0;

            $next = $maxSeq + 1;

            return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Catat satu baris movement berdasarkan angka available_stock/unusable_stock
     * TERKINI di $stock (caller bertanggung jawab sudah mengubah & save() stok
     * sebelum memanggil ini, karena counter mana yang berubah beda-beda per tipe).
     */
    public static function log(
        UniformStock $stock,
        string $type,
        int $signedQuantity,
        ?string $notes,
        int $userId,
        ?string $sourceRecordCode = null,
    ): self {
        return static::create([
            'movement_code' => static::generateMovementCode(),
            'uniform_stock_id' => $stock->id,
            'movement_type' => $type,
            'quantity' => $signedQuantity,
            'available_after' => $stock->available_stock,
            'unusable_after' => $stock->unusable_stock,
            'source_record_id' => $sourceRecordCode,
            'notes' => $notes,
            'created_by' => $userId,
        ]);
    }
}
