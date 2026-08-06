<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sumber kebenaran BARU utk akses modul per user (dicek
     * ModuleAccessMiddleware), menggantikan pengecekan langsung ke
     * module_role. module_role TETAP ada & TIDAK dihapus — tetap dipakai
     * sbg referensi default/saran saat IT membuat akun baru (lihat
     * UserManagementController::create()), tapi bukan lagi yang menentukan
     * akses real-time.
     */
    public function up(): void
    {
        Schema::create('module_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'module_id']);
        });

        $this->backfillFromRoleDefaults();
    }

    /**
     * Backfill WAJIB — supaya user yang sudah ada sekarang tidak kehilangan
     * akses modul begitu fitur ini di-deploy. Pakai DB::table() (bukan
     * Eloquent) supaya tidak tergantung shape model yang bisa berubah di
     * migrasi masa depan. Method terpisah (bukan langsung di up()) supaya
     * bisa dipanggil ulang secara terisolasi dari test — lihat
     * tests/Feature/Database/ModuleUserBackfillTest.php.
     */
    public function backfillFromRoleDefaults(): void
    {
        $pairs = DB::table('role_user')
            ->join('module_role', 'module_role.role_id', '=', 'role_user.role_id')
            ->select('role_user.user_id', 'module_role.module_id')
            ->distinct()
            ->get();

        if ($pairs->isNotEmpty()) {
            $now = now();
            $rows = $pairs->map(fn ($pair) => [
                'user_id' => $pair->user_id,
                'module_id' => $pair->module_id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('module_user')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('module_user');
    }
};
