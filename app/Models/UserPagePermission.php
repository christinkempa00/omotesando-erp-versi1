<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tier akses (view/edit) satu user ke satu halaman GA — lihat
 * User::canEdit(). Tidak ada baris = default 'edit' (di kode, bukan kolom
 * DB) supaya user existing tidak berubah behavior-nya.
 */
class UserPagePermission extends Model
{
    protected $fillable = ['user_id', 'page_key', 'access_level'];

    public const PAGE_REQUESTS = 'requests';
    public const PAGE_ASSETS = 'assets';
    public const PAGE_UNIFORMS_STOCKS = 'uniforms_stocks';
    public const PAGE_UNIFORMS_RECORDS = 'uniforms_records';
    public const PAGE_MAINTENANCE = 'maintenance';
    public const PAGE_WORKLOGS = 'worklogs';

    public const ACCESS_VIEW = 'view';
    public const ACCESS_EDIT = 'edit';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Petakan Module::KEY -> daftar halaman (page_key + label) yang tier
     * aksesnya diatur terpisah. Kebanyakan 1:1 (mis. Aset), tapi Seragam
     * sengaja 2 baris (Stok vs Record) krn memang 2 controller/halaman
     * terpisah di kode. Dipakai form Manajemen User (IT) utk render radio
     * "Lihat saja"/"Bisa edit" di bawah tiap checkbox modul yang relevan.
     *
     * @return array<string, array<int, array{key: string, label: string}>>
     */
    public static function pagesByModuleKey(): array
    {
        return [
            Module::REQUESTS => [
                ['key' => self::PAGE_REQUESTS, 'label' => 'Pengajuan'],
            ],
            Module::ASSETS => [
                ['key' => self::PAGE_ASSETS, 'label' => 'Aset'],
            ],
            Module::UNIFORMS => [
                ['key' => self::PAGE_UNIFORMS_STOCKS, 'label' => 'Seragam — Stok'],
                ['key' => self::PAGE_UNIFORMS_RECORDS, 'label' => 'Seragam — Record'],
            ],
            Module::MAINTENANCE => [
                ['key' => self::PAGE_MAINTENANCE, 'label' => 'Jadwal Pemeliharaan'],
            ],
            Module::WORK_LOG => [
                ['key' => self::PAGE_WORKLOGS, 'label' => 'Work Log'],
            ],
        ];
    }
}
