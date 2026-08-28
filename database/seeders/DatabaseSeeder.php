<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ModuleSeeder::class,
            SystemModuleSeeder::class,
            DivisionSeeder::class,
            BranchSeeder::class,
            BranchLocationSeeder::class,
            UserSeeder::class,
            ItBoardSeeder::class,
        ]);
    }
}
