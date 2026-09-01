<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Seed modul GA yang sudah ada di project ini, dengan akses default
     * role GA/Head/Outlet — ketiganya sama-sama pakai modul ini (GA lewat
     * ga.*, Head lewat head.* monitoring, Outlet lewat outlet.*, semua
     * digerbang module: middleware yang sama, lihat routes/web.php).
     */
    public function run(): void
    {
        $modules = [
            ['key' => Module::REQUESTS, 'label' => 'Request', 'description' => 'Pengajuan GA (GAR)'],
            ['key' => Module::ASSETS, 'label' => 'Inventaris Aset', 'description' => 'Manajemen aset & QR label'],
            ['key' => Module::UNIFORMS, 'label' => 'Inventaris Seragam', 'description' => 'Stok, serah-terima, & movement seragam'],
            ['key' => Module::MAINTENANCE, 'label' => 'Jadwal Pemeliharaan', 'description' => 'Penjadwalan & pencatatan pemeliharaan aset'],
            ['key' => Module::WORK_LOG, 'label' => 'Work Log', 'description' => 'Catatan aktivitas teknisi per outlet, utk kontrol kinerja'],
        ];

        $defaultRoleIds = Role::whereIn('name', [Role::GA, Role::HEAD, Role::OUTLET])->pluck('id');

        foreach ($modules as $data) {
            $module = Module::updateOrCreate(['key' => $data['key']], $data + ['is_active' => true]);
            $module->roles()->syncWithoutDetaching($defaultRoleIds);
        }

        $monitoringRoleIds = Role::whereIn('name', [Role::OUTLET, Role::GA, Role::HEAD])->pluck('id');
        $monitoring = Module::updateOrCreate(
            ['key' => Module::OUTLET_MONITORING],
            ['label' => 'Outlet', 'description' => 'Laporan foto kebersihan outlet saat opening & closing', 'is_active' => true]
        );
        $monitoring->roles()->syncWithoutDetaching($monitoringRoleIds);
    }
}
