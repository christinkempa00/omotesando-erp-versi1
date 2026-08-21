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

        // --- Modul SCM: Produksi, Gudang (terikat Central Kitchen), Outlet
        // (terikat Ironiku) — branch_id dipakai controller SCM utk scope akses.
        // Produksi butuh division_id (material_requests.division_id NOT NULL,
        // sama seperti pola ga_requests) — dipetakan ke divisi Division::SCM.
        $scmDivision = \App\Models\Division::where('code', \App\Models\Division::SCM)->first();

        $produksi = User::updateOrCreate(
            ['email' => 'produksi@omotesando.test'],
            [
                'name' => 'Produksi Omotesando',
                'password' => Hash::make('password'), // GANTI setelah login pertama kali
                'division_id' => $scmDivision?->id,
                'email_verified_at' => now(),
            ]
        );

        $produksiRole = Role::where('name', Role::PRODUKSI)->first();

        if ($produksiRole) {
            $produksi->roles()->syncWithoutDetaching([$produksiRole->id]);
        }

        $centralKitchen = Branch::where('name', 'Central Kitchen')->first();

        $gudang = User::updateOrCreate(
            ['email' => 'gudang@omotesando.test'],
            [
                'name' => 'Gudang Omotesando',
                'password' => Hash::make('password'), // GANTI setelah login pertama kali
                'division_id' => null,
                'branch_id' => $centralKitchen?->id,
                'email_verified_at' => now(),
            ]
        );

        $gudangRole = Role::where('name', Role::GUDANG)->first();

        if ($gudangRole) {
            $gudang->roles()->syncWithoutDetaching([$gudangRole->id]);
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

        // --- Modul Purchasing: Purchasing (approve Purchase Requisition dari
        // Outlet, lalu buat PO kategori 'food') & Finance (approval step
        // terakhir + invoice/pembayaran supplier) — sebelumnya kedua role
        // ini sudah terdaftar di Role.php tapi belum pernah punya akun seeded.
        $purchasing = User::updateOrCreate(
            ['email' => 'purchasing@omotesando.test'],
            [
                'name' => 'Purchasing Omotesando',
                'password' => Hash::make('password'), // GANTI setelah login pertama kali
                'division_id' => null,
                'email_verified_at' => now(),
            ]
        );

        $purchasingRole = Role::where('name', Role::PURCHASING)->first();

        if ($purchasingRole) {
            $purchasing->roles()->syncWithoutDetaching([$purchasingRole->id]);
        }

        $finance = User::updateOrCreate(
            ['email' => 'finance@omotesando.test'],
            [
                'name' => 'Finance Omotesando',
                'password' => Hash::make('password'), // GANTI setelah login pertama kali
                'division_id' => null,
                'email_verified_at' => now(),
            ]
        );

        $financeRole = Role::where('name', Role::FINANCE)->first();

        if ($financeRole) {
            $finance->roles()->syncWithoutDetaching([$financeRole->id]);
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
