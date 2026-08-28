<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\Concerns\HasBranchLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UniformStock extends Model
{
    use HasBranchLocation;

    /**
     * Seragam cuma relevan di outlet berstaf — bukan gudang/kantor seperti
     * Head Office/Central Storage/Central Kitchen.
     */
    public const UNIFORM_OUTLETS = ['Ironiku', 'The Cutler', 'Patty and Sons', 'Ask for Patty', 'Zodiac'];

    protected $fillable = [
        'stock_code',
        'branch_id',
        'branch_location_id',
        'uniform_type',
        'size',
        'color',
        'status',
        'available_stock',
        'unusable_stock',
        'low_stock_threshold',
        'stock_photo_path',
    ];

    protected $casts = [
        'available_stock' => 'integer',
        'unusable_stock' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    // --- Konstanta kondisi/status. Seragam TIDAK punya status "Dalam
    // Pemeliharaan" seperti Asset — seragam yang rusak/tidak layak pakai
    // langsung ditandai Rusak & dipindah ke unusable_stock, bukan "dalam
    // perbaikan" seperti aset fisik yang butuh servis vendor.
    public const STATUS_BAGUS = 'bagus';
    public const STATUS_RUSAK = 'rusak';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_BAGUS => 'Bagus',
            self::STATUS_RUSAK => 'Rusak',
        ];
    }

    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_BAGUS => 'bg-green-100 text-green-700',
            self::STATUS_RUSAK => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    // --- Relasi ---
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(UniformMovement::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(UniformRecord::class);
    }

    // --- Scopes ---
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('low_stock_threshold', '>', 0)
            ->whereColumn('available_stock', '<=', 'low_stock_threshold');
    }

    public function isLowStock(): bool
    {
        return $this->low_stock_threshold > 0 && $this->available_stock <= $this->low_stock_threshold;
    }

    // --- Business logic ---

    /**
     * Kode stok: UNI-{OUTLET}-{TIPE}-{UKURAN}-{WARNA}, mis. UNI-CENKIT-VEST-L-HITAM.
     *
     * Slug tipe/ukuran/warna membuang semua karakter non-alfanumerik (spasi,
     * "#", tanda baca, dst) — jadi dua nama yang beda di layar (mis. "Kemeja
     * Polos #2" vs "Kemeja Polos 2") bisa menghasilkan kode IDENTIK. Supaya
     * itu tidak pernah bentrok sama kode yang sudah dipakai varian lain
     * (unique constraint di kolom stock_code), tambahkan suffix -2, -3, dst
     * sampai kode-nya benar-benar unik. $ignoreId dipakai saat update()
     * supaya varian tidak dianggap "bentrok" sama dirinya sendiri.
     */
    public static function generateStockCode(Branch $branch, string $type, ?string $size, ?string $color, ?int $ignoreId = null): string
    {
        $slug = fn (?string $value) => $value
            ? Str::of($value)->upper()->replaceMatches('/[^A-Z0-9]+/', '')->substr(0, 12)->toString()
            : 'NA';

        $base = collect(['UNI', $branch->code, $slug($type), $slug($size), $slug($color)])
            ->filter()
            ->join('-');

        $code = $base;
        $suffix = 2;

        while (
            static::where('stock_code', $code)
                ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return $code;
    }
}
