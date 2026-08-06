<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ImportAssetPhotos extends Command
{
    protected $signature = 'assets:import-photos
        {source : Path ke folder "02 Inventaris Asset" (berisi subfolder "Foto Asset" dan "Foto Serial Number")}
        {--dry-run : Hanya tampilkan hasil pemetaan tanpa menyalin file atau mengubah assets.json}';

    protected $description = 'Impor foto aset & foto SN dari folder export lama ke storage lokal, lalu update assets.json';

    public function handle(): int
    {
        $source = rtrim($this->argument('source'), '/\\');
        $assetPhotoDir = $source.DIRECTORY_SEPARATOR.'Foto Asset';
        $snPhotoDir = $source.DIRECTORY_SEPARATOR.'Foto Serial Number';

        if (! is_dir($assetPhotoDir) || ! is_dir($snPhotoDir)) {
            $this->error("Folder tidak ditemukan. Pastikan ada:\n- {$assetPhotoDir}\n- {$snPhotoDir}");

            return self::FAILURE;
        }

        $jsonPath = database_path('data/assets.json');
        if (! file_exists($jsonPath)) {
            $this->error("File data tidak ditemukan: {$jsonPath}");

            return self::FAILURE;
        }

        $rows = json_decode(file_get_contents($jsonPath), true);
        if (! is_array($rows)) {
            $this->error('assets.json tidak valid.');

            return self::FAILURE;
        }

        $assetMap = $this->buildLatestFileMap($assetPhotoDir, 'Foto_Aset');
        $snMap = $this->buildLatestFileMap($snPhotoDir, 'Foto_SN');

        $dryRun = (bool) $this->option('dry-run');

        $gotAsset = 0;
        $gotSn = 0;
        $missingAsset = [];
        $missingSn = [];
        $failed = [];

        if (! $dryRun) {
            Storage::disk('public')->makeDirectory('assets/photos');
            Storage::disk('public')->makeDirectory('assets/serial-photos');
        }

        foreach ($rows as &$row) {
            $code = $row['asset_code'] ?? null;
            if (! $code) {
                continue;
            }

            // Foto Aset
            if (isset($assetMap[$code])) {
                $srcFile = $assetMap[$code];
                $destRel = "assets/photos/{$code}.jpg";
                $destAbs = storage_path('app/public/'.$destRel);

                if ($dryRun || $this->safeCopy($srcFile, $destAbs)) {
                    $row['image_path'] = $destRel;
                    $gotAsset++;
                } else {
                    $failed[] = "{$code} (Foto Aset): gagal menyalin {$srcFile}";
                    $row['image_path'] = null;
                    $missingAsset[] = $code;
                }
            } else {
                $row['image_path'] = null;
                $missingAsset[] = $code;
            }

            // Foto SN
            if (isset($snMap[$code])) {
                $srcFile = $snMap[$code];
                $destRel = "assets/serial-photos/{$code}.jpg";
                $destAbs = storage_path('app/public/'.$destRel);

                if ($dryRun || $this->safeCopy($srcFile, $destAbs)) {
                    $row['serial_number_photo_path'] = $destRel;
                    $gotSn++;
                } else {
                    $failed[] = "{$code} (Foto SN): gagal menyalin {$srcFile}";
                    $row['serial_number_photo_path'] = null;
                    $missingSn[] = $code;
                }
            } else {
                $row['serial_number_photo_path'] = null;
                $missingSn[] = $code;
            }
        }
        unset($row);

        if (! $dryRun) {
            file_put_contents(
                $jsonPath,
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').'Hasil impor foto aset:');
        $this->line('Total aset diproses : '.count($rows));
        $this->line("Dapat Foto Aset      : {$gotAsset}");
        $this->line('Tidak dapat Foto Aset: '.count($missingAsset).(count($missingAsset) ? ' -> '.implode(', ', $missingAsset) : ''));
        $this->line("Dapat Foto SN        : {$gotSn}");
        $this->line('Tidak dapat Foto SN  : '.count($missingSn).(count($missingSn) ? ' -> '.implode(', ', $missingSn) : ''));

        if (count($failed)) {
            $this->warn('File gagal disalin ('.count($failed).'):');
            foreach ($failed as $f) {
                $this->line("  - {$f}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Scan folder untuk file berpola AssetFlow_{KODE}_{marker}_{NAMA}_{YYYYMMDD}_{HHMMSS}.jpg
     * dan kembalikan array [asset_code => path_file_terbaru].
     */
    private function buildLatestFileMap(string $dir, string $marker): array
    {
        $files = File::files($dir);
        $latest = []; // code => ['timestamp' => string, 'path' => string]

        $pattern = '/^AssetFlow_(?<code>[A-Za-z0-9\-]+)_'.preg_quote($marker, '/').'_.*?_(?<date>\d{8})_(?<time>\d{6})\.jpg$/i';

        foreach ($files as $file) {
            $name = $file->getFilename();

            if (! preg_match($pattern, $name, $m)) {
                continue;
            }

            $code = strtoupper($m['code']);
            $timestamp = $m['date'].$m['time'];

            if (! isset($latest[$code]) || $timestamp > $latest[$code]['timestamp']) {
                $latest[$code] = [
                    'timestamp' => $timestamp,
                    'path' => $file->getPathname(),
                ];
            }
        }

        return array_map(fn ($v) => $v['path'], $latest);
    }

    private function safeCopy(string $src, string $dest): bool
    {
        try {
            if (! is_readable($src)) {
                return false;
            }

            return copy($src, $dest) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
