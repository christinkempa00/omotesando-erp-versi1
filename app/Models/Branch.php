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
     * Subset outlet dipakai di seluruh modul GA operasional (Asset Inventory,
     * Asset Maintenance Schedule, Asset Request, GA Dashboard) — TANPA
     * Central Storage. Central Storage tetap outlet aktif & tetap dipakai
     * penuh di SCM/Purchasing/IT (gudang sungguhan yang mereka kelola),
     * cuma tidak relevan sebagai pilihan di form-form operasional GA ini.
     */
    public const GA_OUTLETS = [
        'Head Office',
        'Central Kitchen',
        'Ironiku',
        'The Cutler',
        'Ask for Patty',
        'Zodiac',
        'Patty and Sons',
    ];

    public const MONITORING_OUTLETS = [
        'Ironiku',
        'The Cutler',
        'Ask for Patty',
        'Zodiac',
        'Patty and Sons',
    ];
    /**
     * Daftar outlet aktif, terurut sesuai OUTLET_ORDER, tanpa "Outlet Pusat".
     * Kirim $only untuk membatasi ke subset tertentu (mis. modul Seragam yang
     * cuma relevan di outlet berstaf, bukan gudang/kantor). Kirim
     * $alwaysInclude utk memaksa satu outlet tetap ikut walau di luar $only —
     * dipakai di form edit supaya data lama yang sudah terlanjur pakai outlet
     * yang kini disembunyikan (mis. Central Storage) tetap tampil benar,
     * bukan malah hilang dari dropdown & keliru berubah saat disimpan ulang.
     */
    public static function orderedOutlets(?array $only = null, ?string $alwaysInclude = null): \Illuminate\Support\Collection
    {
        $allowed = $only ?? self::OUTLET_ORDER;

        if ($alwaysInclude && ! in_array($alwaysInclude, $allowed, true)) {
            $allowed[] = $alwaysInclude;
        }

        return static::where('is_active', true)->get()
            ->filter(fn (Branch $branch) => in_array($branch->name, $allowed, true))
            ->sortBy(fn (Branch $branch) => array_search($branch->name, self::OUTLET_ORDER, true))
            ->values();
    }
}
