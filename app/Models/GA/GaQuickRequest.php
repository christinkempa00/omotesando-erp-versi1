<?php

namespace App\Models\GA;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Permintaan cepat (bukan alur GAR berjenjang) — dikirim langsung sebagai
 * notifikasi ke grup Telegram GA, untuk kebutuhan barang kecil/mendadak.
 */
class GaQuickRequest extends Model
{
    protected $fillable = [
        'requested_date',
        'branch_id',
        'user_name',
        'pic_name',
        'urgency',
        'needs_description',
        'created_by',
        'sent_at',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public const URGENCY_LOW = 'low';
    public const URGENCY_MEDIUM = 'medium';
    public const URGENCY_HIGH = 'high';

    public static function urgencyLabels(): array
    {
        return [
            self::URGENCY_LOW => 'Low',
            self::URGENCY_MEDIUM => 'Medium',
            self::URGENCY_HIGH => 'High',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GaQuickRequestItem::class);
    }

    /**
     * Format teks pesan Telegram — sama persis dengan yang ditampilkan di
     * panel preview form, supaya WYSIWYG antara preview dan pesan asli.
     */
    public function toTelegramText(): string
    {
        $lines = [];
        $lines[] = '*GA Request*';
        $lines[] = '';
        $lines[] = 'Tanggal: '.$this->requested_date->translatedFormat('l, d F Y');
        $lines[] = 'Outlet: '.($this->branch?->name ?? '-');
        $lines[] = 'User: '.($this->user_name ?: '-');
        $lines[] = 'Person in charge: '.($this->pic_name ?: '-');
        $lines[] = 'Status Urgency: '.(self::urgencyLabels()[$this->urgency] ?? $this->urgency);
        $lines[] = '';
        $lines[] = 'List barang:';

        foreach ($this->items as $i => $item) {
            $lines[] = ($i + 1).'. '.$item->item_name.' - '.$item->qty.' '.($item->unit ?: 'Pcs');
        }

        $lines[] = '';
        $lines[] = 'Penjelasan kebutuhan:';
        $lines[] = '1. '.($this->needs_description ?: '-');

        $links = $this->items->pluck('photo_link')->filter();
        if ($links->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Link/foto barang:';
            foreach ($links as $i => $link) {
                $lines[] = ($i + 1).'. '.$link;
            }
        }

        return implode("\n", $lines);
    }
}
