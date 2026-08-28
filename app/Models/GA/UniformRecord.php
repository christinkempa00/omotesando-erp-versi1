<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\Concerns\HasBranchLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class UniformRecord extends Model
{
    use HasBranchLocation;

    protected $fillable = [
        'record_code',
        'employee_name',
        'issued_by_name',
        'branch_id',
        'branch_location_id',
        'uniform_type',
        'size',
        'color',
        'uniform_stock_id',
        'issue_date',
        'issue_photo_path',
        'signature_path',
        'issued_by_signature_path',
        'issue_notes',
        'status',
        'return_date',
        'return_condition',
        'return_notes',
        'returned_by_name',
        'received_by_name',
        'return_signature_path',
        'received_by_signature_path',
        'qty_sesuai',
        'qty_sesuai_notes',
        'spesifikasi_sesuai',
        'spesifikasi_sesuai_notes',
        'kondisi_sesuai',
        'kondisi_sesuai_notes',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'return_date' => 'date',
        'qty_sesuai' => 'boolean',
        'spesifikasi_sesuai' => 'boolean',
        'kondisi_sesuai' => 'boolean',
    ];

    // --- Status ---
    public const STATUS_ISSUED = 'issued';
    public const STATUS_RETURNED = 'returned';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ISSUED => 'Sedang Dipakai',
            self::STATUS_RETURNED => 'Dikembalikan',
        ];
    }

    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_ISSUED => 'bg-blue-100 text-blue-700',
            self::STATUS_RETURNED => 'bg-green-100 text-green-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    // --- Kondisi pengembalian ---
    // Cuma 2 pilihan (Bagus/Rusak) — barang yang benar-benar tidak kembali
    // (hilang) tidak diproses lewat "Tandai Dikembalikan" sama sekali,
    // jadi tidak butuh opsi kondisi tersendiri utk kasus itu.
    public const CONDITION_GOOD = 'good';
    public const CONDITION_DAMAGED = 'damaged';

    public static function conditionLabels(): array
    {
        return [
            self::CONDITION_GOOD => 'Bagus',
            self::CONDITION_DAMAGED => 'Rusak',
        ];
    }

    public static function conditionBadgeColor(string $condition): string
    {
        return match ($condition) {
            self::CONDITION_GOOD => 'bg-green-100 text-green-700',
            self::CONDITION_DAMAGED => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    // --- Relasi ---
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function uniformStock(): BelongsTo
    {
        return $this->belongsTo(UniformStock::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UniformRecordItem::class);
    }

    // --- Business logic ---

    /**
     * Record lama (sebelum fitur multi-item) tidak punya baris di
     * uniform_record_items sama sekali — dipakai di seluruh view/controller
     * utk cabang tampilan/logika lama vs baru tanpa mengulang query.
     */
    public function isItemized(): bool
    {
        return $this->relationLoaded('items')
            ? $this->items->isNotEmpty()
            : $this->items()->exists();
    }

    /**
     * Ringkasan satu baris utk index/export — "Nama Barang (Spesifikasi)
     * +N lainnya" utk record multi-item, atau field tunggal lama utk record
     * pre-fitur-ini (tetap tampil identik seperti sebelumnya).
     */
    public function summaryLabel(): string
    {
        if ($this->isItemized()) {
            $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
            $first = $items->first();

            $label = $first->uniform_type;
            if ($first->specificationLabel() !== '-') {
                $label .= ' ('.$first->specificationLabel().')';
            }

            $extra = $items->count() - 1;

            return $extra > 0 ? "{$label} +{$extra} lainnya" : $label;
        }

        $label = $this->uniform_type ?: '-';
        $spec = collect([$this->size, $this->color])->filter()->implode(' / ');

        return $spec ? "{$label} ({$spec})" : $label;
    }

    /**
     * Generate kode serah-terima: SRH-2026-0001, reset per tahun.
     *
     * Dihitung dari NOMOR TERBESAR yang sudah dipakai (bukan COUNT baris) —
     * COUNT rentan macet permanen kalau ada gap di penomoran (satu baris
     * gagal/dibatalkan di tengah, atau baris lama terhapus): begitu
     * count()+1 sudah pernah dipakai, count() tidak akan pernah berubah
     * sendiri dan setiap percobaan berikutnya akan terus tabrakan dengan
     * kode yang sama selamanya (lihat bug nyata yang sama persis di
     * UniformMovement::generateMovementCode()).
     */
    public static function generateRecordCode(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $prefix = "SRH-{$year}-";

            $maxSeq = static::lockForUpdate()
                ->whereYear('created_at', $year)
                ->where('record_code', 'like', $prefix.'%')
                ->get(['record_code'])
                ->map(fn (self $r) => (int) substr($r->record_code, strlen($prefix)))
                ->max() ?? 0;

            $next = $maxSeq + 1;

            return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }
}
