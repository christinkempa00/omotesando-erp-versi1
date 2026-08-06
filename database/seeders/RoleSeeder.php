<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => Role::GA, 'description' => 'General Affair'],
            ['name' => Role::FINANCE, 'description' => 'Finance'],
            ['name' => Role::HR, 'description' => 'Human Resources'],
            ['name' => Role::COST_CONTROL, 'description' => 'Cost Control / SCM'],
            ['name' => Role::HEAD, 'description' => 'Head / Approver tertinggi'],
            ['name' => Role::ADMIN, 'description' => 'Administrator sistem'],
            ['name' => Role::IT, 'description' => 'IT — kontrol akses & mode pemeliharaan modul'],
            ['name' => Role::PRODUKSI, 'description' => 'Produksi — pengajuan bahan & batch produksi'],
            ['name' => Role::GUDANG, 'description' => 'Gudang — cetak label & kirim surat jalan'],
            ['name' => Role::OUTLET, 'description' => 'Outlet — terima surat jalan di cabang'],
            ['name' => Role::PURCHASING, 'description' => 'Purchasing — approve Purchase Requisition & buat PO ke supplier'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
