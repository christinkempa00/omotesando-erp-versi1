<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename key modul "Monitoring Outlet" -> "Outlet" (label & key, BUKAN
     * nama konstanta PHP). UPDATE in-place (bukan delete+create) supaya
     * module_id/system_module_id yang sudah ditaut ke akun (mis.
     * ccomotesando@gmail.com lewat module_user) tidak putus — pivot
     * module_user & module_role menaut lewat module_id numerik, bukan
     * string key, jadi rename ini aman (lihat inspeksi 24/08/2026).
     */
    public function up(): void
    {
        DB::table('modules')->where('key', 'outlet_monitoring')->update([
            'key' => 'outlet',
            'label' => 'Outlet',
        ]);

        DB::table('system_modules')->where('key', 'ga.outlet-monitoring')->update([
            'key' => 'ga.outlet',
            'name' => 'Outlet',
        ]);
    }

    public function down(): void
    {
        DB::table('modules')->where('key', 'outlet')->update([
            'key' => 'outlet_monitoring',
            'label' => 'Monitoring Outlet',
        ]);

        DB::table('system_modules')->where('key', 'ga.outlet')->update([
            'key' => 'ga.outlet-monitoring',
            'name' => 'Monitoring Outlet',
        ]);
    }
};
