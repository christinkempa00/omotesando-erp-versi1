<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@omotesando.test'],
            [
                'name' => 'Admin Omotesando',
                'password' => Hash::make('password'), // GANTI setelah login pertama kali
                'division_id' => null,
                'email_verified_at' => now(),
            ]
        );

        $adminRole = Role::where('name', Role::ADMIN)->first();

        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
