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
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
