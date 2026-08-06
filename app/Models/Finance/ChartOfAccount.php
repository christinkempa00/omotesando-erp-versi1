<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
    ];

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_EXPENSE = 'expense';

    public static function typeLabels(): array
    {
        return [
            self::TYPE_ASSET => 'Aset',
            self::TYPE_LIABILITY => 'Liabilitas',
            self::TYPE_EQUITY => 'Ekuitas',
            self::TYPE_REVENUE => 'Pendapatan',
            self::TYPE_EXPENSE => 'Beban',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    /**
     * Saldo akun = debit - credit utk akun bersaldo normal debit (Aset,
     * Beban), atau credit - debit utk akun bersaldo normal kredit
     * (Liabilitas, Ekuitas, Pendapatan). Dipakai Neraca & General Ledger.
     */
    public function balance(): float
    {
        $totalDebit = (float) $this->journalEntryLines()->sum('debit');
        $totalCredit = (float) $this->journalEntryLines()->sum('credit');

        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true)
            ? $totalDebit - $totalCredit
            : $totalCredit - $totalDebit;
    }
}
