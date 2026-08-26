<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Registry modul GA — dipakai Head untuk aktif/nonaktifkan modul & atur
 * role apa saja yang boleh mengaksesnya (lihat ModuleAccessMiddleware).
 */
class Module extends Model
{
    protected $fillable = ['key', 'label', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Key baku modul GA yang sudah ada di project ini — dipakai sebagai
     * referensi supaya tidak ada "magic string" tersebar (mis. di middleware
     * route, seeder). Tambah konstanta baru di sini kalau ada modul baru.
     */
    public const REQUESTS = 'requests';
    public const ASSETS = 'assets';
    public const UNIFORMS = 'uniforms';
    public const MAINTENANCE = 'maintenance';
    public const WORK_LOG = 'work_log';
    public const OUTLET_MONITORING = 'outlet';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'module_role');
    }

    /**
     * User yang punya akses eksplisit ke modul ini (lihat User::modules()
     * & module_user) — sumber kebenaran akses real-time, beda dari
     * roles() di atas yang cuma dipakai sbg saran default saat IT bikin
     * akun baru.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'module_user');
    }

    /**
     * Dipakai ModuleAccessMiddleware & tempat lain (mis. sidebar GA) untuk
     * cek cepat tanpa perlu load relasi roles kalau tidak perlu.
     */
    public static function isEnabled(string $key): bool
    {
        return (bool) static::where('key', $key)->value('is_active');
    }
}
