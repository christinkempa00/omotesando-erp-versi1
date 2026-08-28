<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchLocation;
use Illuminate\Database\Seeder;

class BranchLocationSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('name', 'Ask for Patty')->first();

        if ($branch) {
            BranchLocation::updateOrCreate(
                ['branch_id' => $branch->id, 'name' => 'Gandaria City'],
                ['is_active' => true, 'sort_order' => 0]
            );
        }
    }
}
