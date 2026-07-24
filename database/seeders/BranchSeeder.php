<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::updateOrCreate(
            ['code' => 'PST'],
            [
                'name' => 'Outlet Pusat',
                'address' => null,
                'is_active' => true,
            ]
        );
    }
}
