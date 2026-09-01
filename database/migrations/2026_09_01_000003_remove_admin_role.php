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
 * Dihapus atas permintaan eksplisit (01/09/2026).
 *
 * TIDAK menghapus baris user admin@omotesando.test seperti precedent
 * 2026_08_24_100005 (4 akun seed test di sana benar-benar tidak pernah
 * dipakai) — di production, akun ini ternyata SUDAH dipakai utk membuat
 * data riwayat sungguhan (128 baris `assets.created_by`, kemungkinan besar
 * pengisian awal inventaris saat setup sistem), dan `created_by` di
 * assets/maintenance_jobs/uniform_movements/uniform_records/
 * ga_quick_requests/work_logs BUKAN nullable & tanpa cascade — hapus
 * user-nya akan gagal (FK constraint) atau (kalau dipaksa) menghapus
 * riwayat data asli. Ikut pola yang SUDAH ada di
 * UserManagementController::destroy() utk kasus persis ini: nonaktifkan
 * (is_active=false), JANGAN hapus permanen. Role "Admin"-nya sendiri tetap
 * dihapus (aman, cuma pivot role_user/module_role yang cascade, tidak ada
 * data riwayat lain yang bergantung ke baris Role). TIDAK menyentuh
 * role/akun GA atau role lain sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        $admin = User::where('email', 'admin@omotesando.test')->first();

        if ($admin) {
            $admin->roles()->detach();
            $admin->update(['is_active' => false]);
        }

        Role::where('name', 'Admin')->delete();
    }

    /**
     * Best-effort: mengembalikan baris Role & mengaktifkan lagi akun
     * admin@omotesando.test (harmless kalau tidak jadi dipakai), TAPI TIDAK
     * memulihkan role_user/module_role yang ikut terhapus cascade — itu
     * keputusan struktural, bukan sekadar perubahan skema. Logic bypass di
     * kode juga TIDAK otomatis kembali (lihat commit ini utk daftar
     * filenya kalau perlu revert manual).
     */
    public function down(): void
    {
        Role::updateOrCreate(['name' => 'Admin'], ['description' => 'Administrator sistem']);

        User::where('email', 'admin@omotesando.test')->update(['is_active' => true]);
    }
};
