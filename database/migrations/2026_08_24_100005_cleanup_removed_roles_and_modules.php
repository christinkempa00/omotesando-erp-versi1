<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\SystemModule;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Bagian dari teardown 24/08/2026 (evaluasi sistem menyeluruh) — Role.php/
 * Module.php/SystemModule.php sudah ditrim ke GA/Head/IT/Outlet, tapi baris
 * lama di tabel roles/modules/system_modules TIDAK otomatis hilang (seeder
 * pakai updateOrCreate, tidak pernah delete). Migrasi ini yang benar-benar
 * menghapusnya, sesuai instruksi user: "Hapus role user individual dan
 * modulnya juga" — termasuk 4 akun seed test (produksi@/gudang@/purchasing@/
 * finance@omotesando.test) yang cuma scaffolding utk role yang dihapus,
 * bukan akun orang sungguhan. Lihat README "Riwayat Perubahan" tanggal
 * yang sama. FK role_user/module_role/module_user semuanya
 * cascadeOnDelete(), jadi baris pivot ikut bersih otomatis.
 */
return new class extends Migration
{
    private const REMOVED_ROLE_NAMES = ['Finance', 'HR', 'Cost Control', 'Produksi', 'Gudang', 'Purchasing'];

    private const REMOVED_MODULE_KEYS = [
        'finance', 'scm_materials', 'scm_deliveries', 'scm_reports', 'scm_near_expiry',
        'purchasing_requisitions', 'purchasing_orders', 'purchasing_receipts',
    ];

    private const REMOVED_SYSTEM_MODULE_KEYS = [
        'head.approvals', 'head.scm',
        'scm.materials', 'scm.batches', 'scm.deliveries', 'scm.discrepancies',
        'scm.reports', 'scm.near-expiry', 'scm.stock-value',
        'purchasing.suppliers', 'purchasing.requisitions', 'purchasing.orders',
        'purchasing.receipts', 'purchasing.invoices',
        'finance.chart-of-accounts', 'finance.mappings', 'finance.reports',
    ];

    private const REMOVED_SEED_USER_EMAILS = [
        'produksi@omotesando.test', 'gudang@omotesando.test',
        'purchasing@omotesando.test', 'finance@omotesando.test',
    ];

    public function up(): void
    {
        User::whereIn('email', self::REMOVED_SEED_USER_EMAILS)->delete();
        Role::whereIn('name', self::REMOVED_ROLE_NAMES)->delete();
        Module::whereIn('key', self::REMOVED_MODULE_KEYS)->delete();
        SystemModule::whereIn('key', self::REMOVED_SYSTEM_MODULE_KEYS)->delete();
    }

    /**
     * Best-effort: mengembalikan baris konfigurasi Role/Module/SystemModule
     * (aman & harmless kalau tidak jadi dipakai lagi), TAPI TIDAK memulihkan
     * baris pivot yang ikut terhapus cascade atau 4 akun seed test —
     * itu keputusan struktural, bukan sekadar perubahan skema.
     */
    public function down(): void
    {
        foreach (self::REMOVED_ROLE_NAMES as $name) {
            Role::updateOrCreate(['name' => $name], ['description' => $name]);
        }

        foreach (self::REMOVED_MODULE_KEYS as $key) {
            Module::updateOrCreate(['key' => $key], ['label' => $key, 'is_active' => true]);
        }

        foreach (self::REMOVED_SYSTEM_MODULE_KEYS as $key) {
            SystemModule::updateOrCreate(['key' => $key], ['name' => $key, 'is_under_maintenance' => false]);
        }
    }
};
