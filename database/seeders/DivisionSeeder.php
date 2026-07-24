<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            ['name' => 'General Affair', 'code' => Division::GA],
            ['name' => 'Finance', 'code' => Division::FINANCE],
            ['name' => 'Human Resources', 'code' => Division::HR],
            ['name' => 'Cost Control', 'code' => Division::SCM],
        ];

        foreach ($divisions as $division) {
            Division::updateOrCreate(['code' => $division['code']], $division);
        }
    }
}
