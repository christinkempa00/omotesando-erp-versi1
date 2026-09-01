<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * "Admin" bukan role bisnis nyata spt GA/Head/IT/Outlet — tidak py
 * portal/dashboard sendiri, cuma flag "bypass semua" teknis yang ditempel
 * di banyak tempat inti (ModuleAccessMiddleware, User::canEdit(),
 * CheckModuleMaintenance, GaRequestController, RoleHomeResolver — semua
 * bypass-nya sudah dihapus dari kode di commit yang sama dgn migrasi ini).
 * Dihapus atas permintaan eksplisit (01/09/2026) setelah dikonfirmasi
 * bukan role operasional yang dipakai staf mana pun.
 *
 * Ikut precedent 2026_08_24_100005_cleanup_removed_roles_and_modules:
 * hapus baris role-nya (role_user/module_role ikut cascade) DAN akun seed
 * test yang cuma scaffolding utk role ini (admin@omotesando.test — bukan
 * akun staf sungguhan). TIDAK menyentuh role/akun GA atau role lain sama
 * sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        User::where('email', 'admin@omotesando.test')->delete();
        Role::where('name', 'Admin')->delete();
    }

    /**
     * Best-effort: mengembalikan baris Role (harmless kalau tidak jadi
     * dipakai lagi), TAPI TIDAK memulihkan baris pivot yang ikut terhapus
     * cascade atau akun seed test — itu keputusan struktural, bukan sekadar
     * perubahan skema. Logic bypass di kode juga TIDAK otomatis kembali
     * (lihat commit ini utk daftar filenya kalau perlu revert manual).
     */
    public function down(): void
    {
        Role::updateOrCreate(['name' => 'Admin'], ['description' => 'Administrator sistem']);
    }
};
