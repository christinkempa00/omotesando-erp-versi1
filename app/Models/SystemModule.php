<?php

namespace App\Models;

use App\Models\IT\ItTask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Registry halaman/section GA & Head yang bisa ditandai "Dalam Pemeliharaan"
 * oleh role IT (lihat CheckModuleMaintenance middleware). Terpisah dari
 * Module/ModuleAccessMiddleware (yang dipakai Head utk aktif/nonaktifkan
 * modul secara kasar) — ini per-halaman & dikontrol IT, bukan Head.
 */
class SystemModule extends Model
{
    /**
     * Key baku tiap halaman/section GA & Head — dipakai sebagai referensi
     * supaya tidak ada "magic string" tersebar (route middleware, seeder,
     * sidebar badge).
     */
    public const GA_REQUESTS = 'ga.requests';
    public const GA_ASSETS = 'ga.assets';
    public const GA_UNIFORMS = 'ga.uniforms';
    public const GA_MAINTENANCE = 'ga.maintenance';
    public const GA_WORKLOG = 'ga.worklogs';
    public const GA_OUTLET_MONITORING = 'ga.outlet';
    public const HEAD_DASHBOARD = 'head.dashboard';
    public const HEAD_REQUESTS = 'head.requests';
    public const HEAD_MODULES = 'head.modules';
    public const HEAD_ASSETS = 'head.assets';
    public const HEAD_UNIFORMS = 'head.uniforms';
    public const HEAD_MAINTENANCE = 'head.maintenance';

    protected $fillable = ['key', 'name', 'is_under_maintenance', 'maintenance_note', 'updated_by'];

    protected $casts = [
        'is_under_maintenance' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Task Papan Kerja IT yang di-link ke modul ini (opsional, lihat
     * ItTask::related_module_id) — ditampilkan di halaman Kontrol Modul
     * supaya IT bisa lihat pekerjaan apa yang terkait sebuah modul.
     */
    public function linkedTasks(): HasMany
    {
        return $this->hasMany(ItTask::class, 'related_module_id');
    }

    /**
     * Dipakai CheckModuleMaintenance & sidebar (badge) — cek cepat tanpa
     * perlu load whole model kalau cuma butuh status boolean-nya.
     */
    public static function isUnderMaintenance(string $key): bool
    {
        return (bool) static::where('key', $key)->value('is_under_maintenance');
    }
}
