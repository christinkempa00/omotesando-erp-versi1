<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $head = User::updateOrCreate(
            ['email' => 'head@omotesando.test'],
            [
                'name' => 'Head Omotesando',
                'password' => Hash::make('password'), // GANTI setelah login pertama kali
                'division_id' => null,
                'email_verified_at' => now(),
            ]
        );

        $headRole = Role::where('name', Role::HEAD)->first();

        if ($headRole) {
            $head->roles()->syncWithoutDetaching([$headRole->id]);
        }

        $it = User::updateOrCreate(
            ['email' => 'it@omotesando.test'],
            [
                'name' => 'IT Omotesando',
                'password' => Hash::make('password'), // GANTI setelah login pertama kali
                'division_id' => null,
                'email_verified_at' => now(),
            ]
        );

        $itRole = Role::where('name', Role::IT)->first();

        if ($itRole) {
            $it->roles()->syncWithoutDetaching([$itRole->id]);
        }

        $ironiku = Branch::where('name', 'Ironiku')->first();

        $outlet = User::updateOrCreate(
            ['email' => 'outlet@omotesando.test'],
            [
                'name' => 'Outlet Ironiku',
                'password' => Hash::make('password'), // GANTI setelah login pertama kali
                'division_id' => null,
                'branch_id' => $ironiku?->id,
                'email_verified_at' => now(),
            ]
        );

        $outletRole = Role::where('name', Role::OUTLET)->first();

        if ($outletRole) {
            $outlet->roles()->syncWithoutDetaching([$outletRole->id]);
        }

        // --- Akun GA (General Affair) — Revisi V1 10/08/2026: sebelumnya
        // belum ada akun GA yang di-seed sama sekali meski divisinya sudah
        // terdaftar (Division::GA) & role GA sudah dipakai di banyak
        // middleware. Modul di-attach otomatis dari default module_role GA
        // (Request, Asset Inventory, Uniform Inventory, dst), sama seperti
        // saran default yang dipakai IT saat bikin akun baru lewat UI.
        $gaDivision = Division::where('code', Division::GA)->first();

        $ga = User::updateOrCreate(
            ['email' => 'ga@allez-group.com'],
            [
                'name' => 'GA Omotesando',
                'password' => Hash::make('GA1945.'),
                'division_id' => $gaDivision?->id,
                'email_verified_at' => now(),
            ]
        );

        $gaRole = Role::where('name', Role::GA)->first();

        if ($gaRole) {
            $ga->roles()->syncWithoutDetaching([$gaRole->id]);

            $gaDefaultModuleIds = DB::table('module_role')
                ->where('role_id', $gaRole->id)
                ->pluck('module_id');

            $ga->modules()->syncWithoutDetaching($gaDefaultModuleIds);
        }
    }
}
