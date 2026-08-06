<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Seed 4 modul GA yang sudah ada di project ini, dengan akses default
     * role GA/Admin/Finance — persis sama dengan middleware role:GA,Admin,Finance
     * yang sudah dipasang di grup route ga.* saat ini, supaya seeding ini
     * tidak mengubah perilaku akses yang sudah berjalan.
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

        $defaultRoleIds = Role::whereIn('name', [Role::GA, Role::ADMIN, Role::FINANCE])->pluck('id');

        foreach ($modules as $data) {
            $module = Module::updateOrCreate(['key' => $data['key']], $data + ['is_active' => true]);
            $module->roles()->syncWithoutDetaching($defaultRoleIds);
        }

        // Modul SCM — akses default per section beda dari GA (lihat
        // routes/web.php grup scm.*).
        $scmModules = [
            ['key' => Module::SCM_MATERIALS, 'label' => 'Pengajuan Bahan & Batch Produksi', 'description' => 'Pengajuan bahan, batch produksi, & cetak label', 'roles' => [Role::PRODUKSI, Role::GUDANG, Role::ADMIN]],
            ['key' => Module::SCM_DELIVERIES, 'label' => 'Surat Jalan', 'description' => 'Kirim & terima surat jalan antar outlet', 'roles' => [Role::GUDANG, Role::OUTLET, Role::ADMIN]],
            ['key' => Module::SCM_REPORTS, 'label' => 'Laporan Selisih & Rekap', 'description' => 'Laporan selisih otomatis & rekap periodik', 'roles' => [Role::ADMIN]],
            ['key' => Module::SCM_NEAR_EXPIRY, 'label' => 'Stok Mendekati Kedaluwarsa', 'description' => 'Daftar batch/barang dengan ED mendekati, per lokasi', 'roles' => [Role::GUDANG, Role::ADMIN]],
        ];

        // Modul Purchasing (Fase 2) — akses per section (lihat routes/web.php
        // grup purchasing.). Head SENGAJA tidak dimasukkan (approve lewat
        // Approval Inbox generik, bukan akses langsung ke modul ini).
        $purchasingModules = [
            ['key' => Module::PURCHASING_REQUISITIONS, 'label' => 'Purchase Requisition', 'description' => 'Outlet ajukan requisisi bahan makanan, Purchasing approve', 'roles' => [Role::OUTLET, Role::PURCHASING, Role::ADMIN]],
            ['key' => Module::PURCHASING_ORDERS, 'label' => 'Purchase Order', 'description' => 'Buat & approve PO ke supplier', 'roles' => [Role::PURCHASING, Role::GA, Role::FINANCE, Role::GUDANG, Role::ADMIN]],
            ['key' => Module::PURCHASING_RECEIPTS, 'label' => 'Penerimaan & Invoice Supplier', 'description' => 'Konfirmasi barang diterima & catat invoice/pembayaran supplier', 'roles' => [Role::GUDANG, Role::GA, Role::FINANCE, Role::ADMIN]],
        ];

        // Modul Finance (Fase 3) — Admin & Finance saja.
        $financeModules = [
            ['key' => Module::FINANCE, 'label' => 'Finance', 'description' => 'Chart of Accounts, mapping jurnal otomatis, & laporan keuangan', 'roles' => [Role::ADMIN, Role::FINANCE]],
        ];

        foreach ([...$scmModules, ...$purchasingModules, ...$financeModules] as $data) {
            $roleIds = Role::whereIn('name', $data['roles'])->pluck('id');
            $module = Module::updateOrCreate(
                ['key' => $data['key']],
                ['label' => $data['label'], 'description' => $data['description'], 'is_active' => true]
            );
            $module->roles()->syncWithoutDetaching($roleIds);
        }
    }
}
