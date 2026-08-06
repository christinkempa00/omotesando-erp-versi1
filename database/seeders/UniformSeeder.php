<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\GA\UniformMovement;
use App\Models\GA\UniformStock;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Impor data seragam dari Assets__2_.xlsx (sheet UniformMovements).
 *
 * Sheet UniformStocks & UniformRecords di file itu kosong — jadi uniform_stocks
 * direkonstruksi dari uniform_movements sendiri: dikelompokkan per stockItemId,
 * ambil baris dengan createdAt TERBARU utk snapshot available/unusable akhir,
 * lalu semua baris movement dimasukkan apa adanya (semua "Restock" per data asli).
 */
class UniformSeeder extends Seeder
{
    public function run(): void
    {
        $path = $this->resolveFilePath();

        if (! $path) {
            $this->command?->error(
                'File Excel tidak ditemukan. Taruh file (mis. "Assets__2_.xlsx") di storage/app/import/ lalu jalankan ulang: '.
                'php artisan db:seed --class="Database\\Seeders\\UniformSeeder"'
            );

            return;
        }

        $user = User::first();
        if (! $user) {
            $this->command?->error('Tidak ada user. Jalankan UserSeeder dulu (php artisan db:seed).');

            return;
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('UniformMovements');

        if (! $sheet) {
            $available = collect($spreadsheet->getSheetNames())->join(', ');
            $this->command?->error("Sheet \"UniformMovements\" tidak ditemukan. Sheet yang ada: {$available}");

            return;
        }

        $rows = $this->sheetToAssoc($sheet);

        if (empty($rows)) {
            $this->command?->warn('Sheet UniformMovements kosong, tidak ada yang diimpor.');

            return;
        }

        // 1) Rekonstruksi uniform_stocks: kelompokkan per stockItemId, ambil
        //    snapshot dari baris ber-createdAt TERBARU.
        $byStockItem = collect($rows)->groupBy(
            fn ($r) => (string) $this->col($r, ['stockitemid', 'stockid', 'stock_item_id'])
        );

        $branchCache = [];
        $stockByItemId = [];

        foreach ($byStockItem as $stockItemId => $itemRows) {
            $latest = $itemRows->sortByDesc(
                fn ($r) => $this->parseDate($this->col($r, ['createdat', 'created_at', 'date']))?->timestamp ?? 0
            )->first();

            $outlet = trim((string) $this->col($latest, ['outlet', 'branch', 'cabang'])) ?: 'Outlet Pusat';
            $type = trim((string) $this->col($latest, ['uniformtype', 'type', 'tipe'])) ?: 'Seragam';
            $size = $this->nullableString($this->col($latest, ['size', 'ukuran']));
            $color = $this->nullableString($this->col($latest, ['color', 'warna']));
            $availableAfter = max(0, (int) $this->col($latest, ['availableafter', 'available_after']));
            $unusableAfter = max(0, (int) $this->col($latest, ['unusableafter', 'unusable_after']));

            if (! isset($branchCache[$outlet])) {
                $branchCache[$outlet] = Branch::firstOrCreate(
                    ['code' => $this->outletCode($outlet)],
                    ['name' => $outlet, 'is_active' => true]
                );
            }
            $branch = $branchCache[$outlet];

            $stock = UniformStock::create([
                'stock_code' => UniformStock::generateStockCode($branch, $type, $size, $color),
                'branch_id' => $branch->id,
                'uniform_type' => $type,
                'size' => $size,
                'color' => $color,
                'available_stock' => $availableAfter,
                'unusable_stock' => $unusableAfter,
                'low_stock_threshold' => 0,
            ]);

            $stockByItemId[$stockItemId] = $stock;
        }

        // 2) Masukkan semua baris movement apa adanya, urut createdAt supaya
        //    riwayatnya kronologis. Kode movement dibuat manual per tahun supaya
        //    tidak bergantung pada tahun "sekarang" saat seeding data historis.
        $sorted = collect($rows)->sortBy(
            fn ($r) => $this->parseDate($this->col($r, ['createdat', 'created_at', 'date']))?->timestamp ?? 0
        )->values();

        $sequencePerYear = [];
        $created = 0;
        $skipped = 0;

        foreach ($sorted as $row) {
            $stockItemId = (string) $this->col($row, ['stockitemid', 'stockid', 'stock_item_id']);
            $stock = $stockByItemId[$stockItemId] ?? null;

            if (! $stock) {
                $skipped++;

                continue;
            }

            $createdAt = $this->parseDate($this->col($row, ['createdat', 'created_at', 'date'])) ?? now();
            $year = $createdAt->year;
            $sequencePerYear[$year] = ($sequencePerYear[$year] ?? 0) + 1;
            $movementCode = 'MOV-'.$year.'-'.str_pad((string) $sequencePerYear[$year], 4, '0', STR_PAD_LEFT);

            $rawType = Str::lower(trim((string) $this->col($row, ['movementtype', 'movement_type', 'type'])));
            $movementType = match (true) {
                str_contains($rawType, 'restock') => UniformMovement::TYPE_RESTOCK,
                str_contains($rawType, 'issue') => UniformMovement::TYPE_ISSUE,
                str_contains($rawType, 'return') => UniformMovement::TYPE_RETURN,
                str_contains($rawType, 'adjust') => UniformMovement::TYPE_ADJUSTMENT,
                str_contains($rawType, 'dispos') => UniformMovement::TYPE_DISPOSAL,
                default => UniformMovement::TYPE_RESTOCK,
            };

            $quantity = (int) $this->col($row, ['quantity', 'qty']);
            if ($quantity === 0) {
                $quantity = $movementType === UniformMovement::TYPE_RESTOCK ? $stock->available_stock : 0;
            }

            UniformMovement::create([
                'movement_code' => $movementCode,
                'uniform_stock_id' => $stock->id,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'available_after' => max(0, (int) $this->col($row, ['availableafter', 'available_after'])),
                'unusable_after' => max(0, (int) $this->col($row, ['unusableafter', 'unusable_after'])),
                'source_record_id' => $this->nullableString($this->col($row, ['sourcerecordid', 'source_record_id'])),
                'notes' => $this->nullableString($this->col($row, ['notes', 'note', 'catatan'])),
                'created_by' => $user->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $created++;
        }

        $this->command?->info(
            'Import selesai: '.count($stockByItemId)." varian stok direkonstruksi, {$created} movement dibuat".
            ($skipped ? ", {$skipped} baris dilewati (stockItemId tidak cocok)" : '').'.'
        );
    }

    /**
     * Cari file .xlsx di storage/app/import — utamakan nama yang mengandung "assets".
     */
    private function resolveFilePath(): ?string
    {
        $dir = storage_path('app/import');

        if (! is_dir($dir)) {
            return null;
        }

        $candidates = collect(File::allFiles($dir))
            ->filter(fn ($f) => Str::lower($f->getExtension()) === 'xlsx');

        $preferred = $candidates->first(fn ($f) => Str::contains(Str::lower($f->getFilename()), 'assets'));

        return ($preferred ?? $candidates->first())?->getPathname();
    }

    /**
     * Baca satu sheet jadi array asosiatif per baris, key = header baris pertama
     * dinormalisasi (lowercase, tanpa spasi/underscore) supaya cocok longgar
     * dengan variasi nama kolom (stockItemId, Stock Item Id, stock_item_id, dst).
     */
    private function sheetToAssoc($sheet): array
    {
        $grid = $sheet->toArray(null, true, true, false);

        if (empty($grid)) {
            return [];
        }

        $header = array_map(
            fn ($h) => Str::lower(str_replace([' ', '_', '-'], '', trim((string) $h))),
            array_shift($grid)
        );

        $rows = [];
        foreach ($grid as $line) {
            if (collect($line)->every(fn ($v) => $v === null || $v === '')) {
                continue; // lewati baris kosong
            }

            $line = array_slice(array_pad($line, count($header), null), 0, count($header));
            $rows[] = array_combine($header, $line);
        }

        return $rows;
    }

    /**
     * Ambil nilai dari baris (hasil sheetToAssoc) berdasarkan daftar nama kolom
     * kandidat yang sudah dinormalisasi (lihat sheetToAssoc).
     */
    private function col(?array $row, array $candidates): mixed
    {
        if (! $row) {
            return null;
        }

        foreach ($candidates as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value));
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Buat kode branch pendek & unik dari nama outlet — identik dengan
     * AssetSeeder::outletCode() supaya cocok dengan branch yang sudah ada.
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
