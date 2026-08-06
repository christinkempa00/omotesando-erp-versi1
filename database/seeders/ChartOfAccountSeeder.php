<?php

namespace Database\Seeders;

use App\Models\Finance\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Set akun dasar umum — 5 header (satu per tipe) + akun anak yang
     * dibutuhkan 3 mapping wajib Fase 3 (Kas, Persediaan, Hutang Usaha,
     * Beban Operasional). Modal & Penjualan disertakan utk kelengkapan
     * struktur COA meski belum ada auto-posting yang memakainya.
     */
    public function run(): void
    {
        $headers = [
            ['code' => '1000', 'name' => 'Aset', 'type' => ChartOfAccount::TYPE_ASSET],
            ['code' => '2000', 'name' => 'Liabilitas', 'type' => ChartOfAccount::TYPE_LIABILITY],
            ['code' => '3000', 'name' => 'Ekuitas', 'type' => ChartOfAccount::TYPE_EQUITY],
            ['code' => '4000', 'name' => 'Pendapatan', 'type' => ChartOfAccount::TYPE_REVENUE],
            ['code' => '5000', 'name' => 'Beban', 'type' => ChartOfAccount::TYPE_EXPENSE],
        ];

        $headerIds = [];
        foreach ($headers as $data) {
            $headerIds[$data['code']] = ChartOfAccount::updateOrCreate(['code' => $data['code']], $data)->id;
        }

        $children = [
            ['code' => '1100', 'name' => 'Kas', 'type' => ChartOfAccount::TYPE_ASSET, 'parent' => '1000'],
            ['code' => '1200', 'name' => 'Persediaan', 'type' => ChartOfAccount::TYPE_ASSET, 'parent' => '1000'],
            ['code' => '2100', 'name' => 'Hutang Usaha', 'type' => ChartOfAccount::TYPE_LIABILITY, 'parent' => '2000'],
            ['code' => '3100', 'name' => 'Modal', 'type' => ChartOfAccount::TYPE_EQUITY, 'parent' => '3000'],
            ['code' => '4100', 'name' => 'Penjualan', 'type' => ChartOfAccount::TYPE_REVENUE, 'parent' => '4000'],
            ['code' => '5100', 'name' => 'Beban Operasional', 'type' => ChartOfAccount::TYPE_EXPENSE, 'parent' => '5000'],
        ];

        foreach ($children as $data) {
            ChartOfAccount::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'parent_id' => $headerIds[$data['parent']],
                ]
            );
        }
    }
}
