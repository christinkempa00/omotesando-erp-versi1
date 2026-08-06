<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class UniformRecord extends Model
{
    protected $fillable = [
        'record_code',
        'employee_name',
        'branch_id',
        'uniform_type',
        'size',
        'color',
        'uniform_stock_id',
        'issue_date',
        'issue_photo_path',
        'signature_path',
        'issue_notes',
        'status',
        'return_date',
        'return_condition',
        'return_notes',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'return_date' => 'date',
    ];

    // --- Status ---
    public const STATUS_ISSUED = 'issued';
    public const STATUS_RETURNED = 'returned';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ISSUED => 'Dipakai',
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
    public const CONDITION_GOOD = 'good';
    public const CONDITION_DAMAGED = 'damaged';
    public const CONDITION_LOST = 'lost';

    public static function conditionLabels(): array
    {
        return [
            self::CONDITION_GOOD => 'Baik',
            self::CONDITION_DAMAGED => 'Rusak',
            self::CONDITION_LOST => 'Hilang',
        ];
    }

    public static function conditionBadgeColor(string $condition): string
    {
        return match ($condition) {
            self::CONDITION_GOOD => 'bg-green-100 text-green-700',
            self::CONDITION_DAMAGED => 'bg-red-100 text-red-700',
            self::CONDITION_LOST => 'bg-orange-100 text-orange-700',
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

    // --- Business logic ---

    /**
     * Generate kode serah-terima: SRH-2026-0001, reset per tahun.
     */
    public static function generateRecordCode(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $count = static::lockForUpdate()
                ->whereYear('created_at', $year)
                ->count();

            $next = $count + 1;

            return 'SRH-'.$year.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }
}
