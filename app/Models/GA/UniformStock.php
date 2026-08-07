<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UniformStock extends Model
{
    /**
     * Seragam cuma relevan di outlet berstaf — bukan gudang/kantor seperti
     * Head Office/Central Storage/Central Kitchen.
     */
    public const UNIFORM_OUTLETS = ['Ironiku', 'The Cutler', 'Patty and Sons', 'Ask for Patty', 'Zodiac'];

    protected $fillable = [
        'stock_code',
        'branch_id',
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

    // --- Konstanta kondisi/status (sama kosakata dengan Asset) ---
    public const STATUS_BAGUS = 'bagus';
    public const STATUS_RUSAK = 'rusak';
    public const STATUS_MAINTENANCE = 'dalam_pemeliharaan';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_BAGUS => 'Bagus',
            self::STATUS_MAINTENANCE => 'Dalam Pemeliharaan',
            self::STATUS_RUSAK => 'Rusak',
        ];
    }

    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_BAGUS => 'bg-green-100 text-green-700',
            self::STATUS_MAINTENANCE => 'bg-yellow-100 text-yellow-700',
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
     */
    public static function generateStockCode(Branch $branch, string $type, ?string $size, ?string $color): string
    {
        $slug = fn (?string $value) => $value
            ? Str::of($value)->upper()->replaceMatches('/[^A-Z0-9]+/', '')->substr(0, 12)->toString()
            : 'NA';

        return collect(['UNI', $branch->code, $slug($type), $slug($size), $slug($color)])
            ->filter()
            ->join('-');
    }

    /**
     * Teks notifikasi Telegram GA (add/edit/delete varian Manajemen Stok
     * Seragam) — lihat TelegramNotifier & pemanggilnya di
     * UniformStockController.
     */
    public function telegramText(string $action, User $actor): string
    {
        $label = [
            'created' => 'Varian Seragam Baru',
            'updated' => 'Varian Seragam Diperbarui',
            'deleted' => 'Varian Seragam Dihapus',
        ][$action];

        return "*{$label}*\n"
            ."Kode: {$this->stock_code}\n"
            ."Tipe: {$this->uniform_type} (Size {$this->size})\n"
            .'Outlet: '.($this->branch?->name ?: '-')."\n"
            .'Oleh: '.$actor->name;
    }
}
