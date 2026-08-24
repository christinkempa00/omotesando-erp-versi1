<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Laporan kebersihan outlet saat opening/closing — diisi akun Outlet
 * (role Outlet, terikat branch_id-nya sendiri), dipantau GA & Head
 * secara read-only. Satu laporan = wadah dokumentasi banyak foto
 * (outlet_report_photos), 1 opening + 1 closing per outlet per hari.
 */
class OutletReport extends Model
{
    protected $fillable = [
        'branch_id',
        'report_date',
        'session',
        'notes',
        'submitted_by',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public const SESSION_OPENING = 'opening';
    public const SESSION_CLOSING = 'closing';

    public static function sessionLabels(): array
    {
        return [
            self::SESSION_OPENING => 'Opening',
            self::SESSION_CLOSING => 'Closing',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OutletReportPhoto::class);
    }
}
