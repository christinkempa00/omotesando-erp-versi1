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
            ['name' => Role::HEAD, 'description' => 'Head / Approver tertinggi'],
            ['name' => Role::ADMIN, 'description' => 'Administrator sistem'],
            ['name' => Role::IT, 'description' => 'IT — kontrol akses & mode pemeliharaan modul'],
            ['name' => Role::OUTLET, 'description' => 'Outlet — laporan foto kebersihan (Monitoring Outlet)'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
