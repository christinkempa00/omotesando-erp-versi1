<?php

namespace Database\Seeders;

use App\Models\SystemModule;
use Illuminate\Database\Seeder;

class SystemModuleSeeder extends Seeder
{
    /**
     * Seed daftar halaman/section GA & Head yang sudah ada di sidebar saat
     * ini, supaya IT langsung bisa mengontrol mode pemeliharaannya tanpa
     * perlu tambah data manual dulu.
     */
    public function run(): void
    {
        $modules = [
            ['key' => SystemModule::GA_REQUESTS, 'name' => 'Pengajuan GA'],
            ['key' => SystemModule::GA_ASSETS, 'name' => 'Inventaris Aset'],
            ['key' => SystemModule::GA_UNIFORMS, 'name' => 'Inventaris Seragam'],
            ['key' => SystemModule::GA_MAINTENANCE, 'name' => 'Jadwal Pemeliharaan'],
            ['key' => SystemModule::GA_WORKLOG, 'name' => 'Work Log'],
            ['key' => SystemModule::GA_OUTLET_MONITORING, 'name' => 'Outlet'],
            ['key' => SystemModule::HEAD_DASHBOARD, 'name' => 'Dashboard Head'],
            ['key' => SystemModule::HEAD_REQUESTS, 'name' => 'Semua Pengajuan (Head)'],
            ['key' => SystemModule::HEAD_MODULES, 'name' => 'Kontrol Modul (Head)'],
            ['key' => SystemModule::HEAD_ASSETS, 'name' => 'Monitoring Aset (Head)'],
            ['key' => SystemModule::HEAD_UNIFORMS, 'name' => 'Monitoring Seragam (Head)'],
            ['key' => SystemModule::HEAD_MAINTENANCE, 'name' => 'Monitoring Pemeliharaan (Head)'],
        ];

        foreach ($modules as $data) {
            SystemModule::updateOrCreate(['key' => $data['key']], $data + ['is_under_maintenance' => false]);
        }
    }
}
