<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\GA\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/assets.json');

        if (! file_exists($path)) {
            $this->command?->error("File data tidak ditemukan: {$path}");

            return;
        }

        $rows = json_decode(file_get_contents($path), true);

        if (! is_array($rows) || count($rows) === 0) {
            $this->command?->error('assets.json kosong atau tidak valid.');

            return;
        }

        // Butuh minimal 1 user untuk kolom created_by (wajib).
        $user = User::first();
        if (! $user) {
            $this->command?->error('Tidak ada user. Jalankan UserSeeder dulu (php artisan db:seed).');

            return;
        }

        // 1) Pastikan semua outlet dari data punya branch. Buat kalau belum ada.
        $outlets = collect($rows)->pluck('outlet')->filter()->unique()->values();
        $branchByOutlet = [];

        foreach ($outlets as $outlet) {
            $branch = Branch::firstOrCreate(
                ['code' => $this->outletCode($outlet)],
                ['name' => $outlet, 'is_active' => true]
            );
            $branchByOutlet[$outlet] = $branch->id;
        }

        // Fallback branch (kalau ada aset tanpa outlet).
        $fallbackBranchId = Branch::query()->value('id')
            ?? Branch::create(['name' => 'Outlet Pusat', 'code' => 'PST', 'is_active' => true])->id;

        // 2) Hapus SEMUA aset lama (data yang sudah ada di sistem).
        //    Nonaktifkan FK check sebentar supaya truncate aman walau ada relasi.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Asset::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 3) Masukkan data baru dari spreadsheet.
        $now = now();
        $inserted = 0;

        foreach ($rows as $r) {
            $outlet = $r['outlet'] ?? null;
            $branchId = ($outlet && isset($branchByOutlet[$outlet]))
                ? $branchByOutlet[$outlet]
                : $fallbackBranchId;

            Asset::create([
                'asset_code' => $r['asset_code'],
                'name' => $r['name'] ?? 'Tanpa Nama',
                'category' => $r['category'] ?? null,
                'brand' => $r['brand'] ?? null,
                'model' => $r['model'] ?? null,
                'serial_number' => $r['serial_number'] ?? null,
                'purchase_price' => $r['purchase_price'] ?? null,
                'purchase_date' => $r['purchase_date'] ?? null,
                'warranty_expiry' => $r['warranty_expiry'] ?? null,
                'vendor_contact' => $r['vendor_contact'] ?? null,
                'status' => $r['status'] ?? 'bagus',
                'location' => $r['location'] ?? null,
                'branch_id' => $branchId,
                'custodian_name' => $r['custodian_name'] ?? null,
                'description' => $r['description'] ?? null,
                'image_path' => $r['image_path'] ?? null,
                'sp3_number' => $r['sp3_number'] ?? null,
                'po_number' => $r['po_number'] ?? null,
                'receive_date' => $r['receive_date'] ?? null,
                'dimension_p' => $r['dimension_p'] ?? null,
                'dimension_l' => $r['dimension_l'] ?? null,
                'dimension_t' => $r['dimension_t'] ?? null,
                'quantity' => $r['quantity'] ?? 1,
                'depreciation_value' => $r['depreciation_value'] ?? null,
                'serial_number_photo_path' => $r['serial_number_photo_path'] ?? null,
                'notes' => $r['notes'] ?? null,
                'created_by' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $inserted++;
        }

        $this->command?->info("Import selesai: {$inserted} aset dimasukkan, ".count($branchByOutlet).' outlet/branch disiapkan.');
    }

    /**
     * Buat kode branch pendek & unik dari nama outlet.
     * "Central Kitchen" -> "CENKIT", "The Cutler" -> "THECUT", dst.
     */
    private function outletCode(string $outlet): string
    {
        $words = preg_split('/\s+/', trim($outlet));
        $code = '';

        foreach ($words as $w) {
            $code .= Str::upper(Str::substr($w, 0, 3));
        }

        return Str::substr($code, 0, 10) ?: Str::upper(Str::substr($outlet, 0, 6));
    }
}