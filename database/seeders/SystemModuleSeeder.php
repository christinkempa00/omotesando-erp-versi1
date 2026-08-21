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
            ['key' => SystemModule::GA_OUTLET_MONITORING, 'name' => 'Monitoring Outlet'],
            ['key' => SystemModule::HEAD_DASHBOARD, 'name' => 'Dashboard Head'],
            ['key' => SystemModule::HEAD_REQUESTS, 'name' => 'Semua Pengajuan (Head)'],
            ['key' => SystemModule::HEAD_APPROVALS, 'name' => 'Approval Inbox (Head)'],
            ['key' => SystemModule::HEAD_MODULES, 'name' => 'Kontrol Modul (Head)'],
            ['key' => SystemModule::HEAD_ASSETS, 'name' => 'Monitoring Aset (Head)'],
            ['key' => SystemModule::HEAD_UNIFORMS, 'name' => 'Monitoring Seragam (Head)'],
            ['key' => SystemModule::HEAD_MAINTENANCE, 'name' => 'Monitoring Pemeliharaan (Head)'],
            ['key' => SystemModule::SCM_MATERIALS, 'name' => 'Pengajuan Bahan'],
            ['key' => SystemModule::SCM_BATCHES, 'name' => 'Batch Produksi'],
            ['key' => SystemModule::SCM_DELIVERIES, 'name' => 'Surat Jalan'],
            ['key' => SystemModule::SCM_DISCREPANCIES, 'name' => 'Laporan Selisih'],
            ['key' => SystemModule::SCM_REPORTS, 'name' => 'Rekap Periodik SCM'],
            ['key' => SystemModule::SCM_NEAR_EXPIRY, 'name' => 'Stok Mendekati Kedaluwarsa'],
            ['key' => SystemModule::SCM_STOCK_VALUE, 'name' => 'Laporan Nilai Persediaan'],
            ['key' => SystemModule::HEAD_SCM, 'name' => 'Monitoring SCM (Head)'],
            ['key' => SystemModule::PURCHASING_SUPPLIERS, 'name' => 'Data Supplier'],
            ['key' => SystemModule::PURCHASING_REQUISITIONS, 'name' => 'Purchase Requisition'],
            ['key' => SystemModule::PURCHASING_ORDERS, 'name' => 'Purchase Order'],
            ['key' => SystemModule::PURCHASING_RECEIPTS, 'name' => 'Penerimaan Barang Supplier'],
            ['key' => SystemModule::PURCHASING_INVOICES, 'name' => 'Invoice & Pembayaran Supplier'],
            ['key' => SystemModule::FINANCE_CHART_OF_ACCOUNTS, 'name' => 'Chart of Accounts'],
            ['key' => SystemModule::FINANCE_MAPPINGS, 'name' => 'Mapping Akun Jurnal'],
            ['key' => SystemModule::FINANCE_REPORTS, 'name' => 'Laporan Keuangan'],
        ];

        foreach ($modules as $data) {
            SystemModule::updateOrCreate(['key' => $data['key']], $data + ['is_under_maintenance' => false]);
        }
    }
}
