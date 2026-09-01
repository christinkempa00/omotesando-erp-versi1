<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * '/dashboard' (ringkasan statistik semua modul GA) selama ini TIDAK
 * digerbang module_user sama sekali — setiap akun GA selalu lihat &
 * bisa akses dashboard apa pun modul yang benar-benar dicentang utknya.
 * Ketahuan dari kasus nyata: Amanda cuma dikasih modul Inventaris
 * Seragam, tapi tetap lihat ringkasan Aset/Pemeliharaan/dll di dashboard
 * yang sama sekali tidak relevan/bisa dia akses lebih jauh.
 *
 * Migrasi ini menyamakan Dashboard dgn modul GA lain: toggle-able lewat
 * Akses Modul di form Manajemen User, digerbang module: middleware di
 * routes/web.php, RoleHomeResolver diperbarui utk mendarat ke modul
 * pertama yang benar-benar dipunya akun ybs kalau Dashboard tidak
 * dicentang (lihat gaHomeRoute()).
 *
 * Backfill: SEMUA akun GA yang sudah ada SEKARANG dapat modul ini (zero
 * regression, sesuai perilaku lama yg selalu bisa akses dashboard) --
 * KECUALI amanda.hr@allez-group.com, sesuai permintaan eksplisit IT
 * (01/09/2026) supaya akun ybs cuma py akses ke Inventaris Seragam saja,
 * tanpa Dashboard.
 */
return new class extends Migration
{
    private const EXCLUDED_EMAILS = ['amanda.hr@allez-group.com'];

    public function up(): void
    {
        $module = Module::updateOrCreate(
            ['key' => Module::DASHBOARD],
            ['label' => 'Dashboard', 'description' => 'Ringkasan statistik semua modul GA', 'is_active' => true]
        );

        $gaRole = Role::where('name', Role::GA)->first();

        if (! $gaRole) {
            return;
        }

        $module->roles()->syncWithoutDetaching([$gaRole->id]);

        $excludedIds = User::whereIn('email', self::EXCLUDED_EMAILS)->pluck('id');

        $gaUserIds = DB::table('role_user')
            ->where('role_id', $gaRole->id)
            ->pluck('user_id')
            ->diff($excludedIds);

        if ($gaUserIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $gaUserIds->map(fn ($userId) => [
            'user_id' => $userId,
            'module_id' => $module->id,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('module_user')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        // module_role & module_user ikut terhapus (cascadeOnDelete).
        Module::where('key', Module::DASHBOARD)->delete();
    }
};
