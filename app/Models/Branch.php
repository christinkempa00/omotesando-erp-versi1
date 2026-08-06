<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'code', 'address', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Urutan outlet baku dipakai di seluruh modul GA (dropdown, filter, dll).
     * "Outlet Pusat" sengaja tidak disertakan di sini — bukan outlet fisik
     * berstaf, jadi tidak relevan di form-form operasional GA.
     */
    public const OUTLET_ORDER = [
        'Head Office',
        'Central Storage',
        'Central Kitchen',
        'Ironiku',
        'The Cutler',
        'Ask for Patty',
        'Zodiac',
        'Patty and Sons',
    ];

    /**
     * Subset outlet khusus dropdown Work Log (GA) — TANPA Head Office/Central
     * Storage, cuma outlet berstaf teknisi + Central Kitchen.
     */
    public const WORK_LOG_OUTLETS = [
        'Ironiku',
        'The Cutler',
        'Ask for Patty',
        'Patty and Sons',
        'Zodiac',
        'Central Kitchen',
    ];

    /**
     * Daftar outlet aktif, terurut sesuai OUTLET_ORDER, tanpa "Outlet Pusat".
     * Kirim $only untuk membatasi ke subset tertentu (mis. modul Seragam yang
     * cuma relevan di outlet berstaf, bukan gudang/kantor).
     */
    public static function orderedOutlets(?array $only = null): \Illuminate\Support\Collection
    {
        $allowed = $only ?? self::OUTLET_ORDER;

        return static::where('is_active', true)->get()
            ->filter(fn (Branch $branch) => in_array($branch->name, $allowed, true))
            ->sortBy(fn (Branch $branch) => array_search($branch->name, self::OUTLET_ORDER, true))
            ->values();
    }
}
