<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\Concerns\HasBranchLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Asset extends Model
{
    use HasBranchLocation;

    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'brand',
        'model',
        'serial_number',
        'purchase_price',
        'purchase_date',
        'warranty_expiry',
        'vendor_contact',
        'status',
        'location',
        'branch_id',
        'branch_location_id',
        'custodian_name',
        'description',
        'image_path',
        'sp3_number',
        'po_number',
        'receive_date',
        'dimension_p',
        'dimension_l',
        'dimension_t',
        'quantity',
        'depreciation_value',
        'serial_number_photo_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'receive_date' => 'date',
        'dimension_p' => 'decimal:2',
        'dimension_l' => 'decimal:2',
        'dimension_t' => 'decimal:2',
        'depreciation_value' => 'decimal:2',
    ];

    // --- Konstanta kondisi/status (disederhanakan jadi 3, sama dengan Seragam) ---
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function maintenanceJobs(): HasMany
    {
        return $this->hasMany(MaintenanceJob::class);
    }

    // --- Business logic ---

    /**
     * Generate kode aset unik: AST-0001, AST-0002, dst. Kontinu selamanya
     * (tidak reset per tahun, karena aset itu barang fisik jangka panjang).
     */
    public static function generateAssetCode(): string
    {
        return DB::transaction(function () {
            $count = static::lockForUpdate()->count();
            $next = $count + 1;

            return 'AST-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }
}
