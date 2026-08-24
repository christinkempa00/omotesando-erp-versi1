<?php

namespace App\Services\GA;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Menempel watermark (waktu server + nama outlet + GPS) ke pixel foto laporan
 * outlet, lalu menyimpannya ke disk 'public'. Sengaja pakai GD murni
 * (bawaan PHP, TIDAK butuh exec()/binary eksternal) karena server Hostinger
 * mematikan exec() — Imagick/wkhtmltopdf dsb tidak tersedia di sana.
 *
 * Font memakai DejaVuSans.ttf yang sudah ikut ter-bundle via dompdf
 * (vendor/dompdf/dompdf/lib/fonts) supaya tidak perlu memasang font baru
 * ke server.
 *
 * CATATAN keandalan: GPS berasal dari browser (navigator.geolocation) & bisa
 * dipalsukan klien — watermark ini menaikkan friksi & memberi konteks, BUKAN
 * bukti anti-palsu. Yang benar-benar terpercaya adalah waktu server (taken_at)
 * & nama outlet (terikat akun), keduanya juga ikut ditempel di sini.
 */
class PhotoWatermarkService
{
    private const FONT_PATH = 'vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf';

    private const FONT_PATH_BOLD = 'vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf';

    /**
     * Proses satu foto: baca, tempel watermark, simpan ke $directory pada
     * disk public, kembalikan path relatif untuk disimpan di DB.
     */
    public function stampAndStore(
        UploadedFile $file,
        string $directory,
        string $outletName,
        float $latitude,
        float $longitude,
        Carbon $takenAt
    ): string {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Ekstensi GD tidak aktif di server — watermark foto tidak bisa diproses. Hubungi IT.');
        }

        $image = $this->createImageFromUpload($file);

        try {
            $this->drawWatermark($image, $outletName, $latitude, $longitude, $takenAt);
            $path = $directory.'/'.$this->generateFilename();
            $this->saveJpeg($image, $path);
        } finally {
            imagedestroy($image);
        }

        return $path;
    }

    private function createImageFromUpload(UploadedFile $file): \GdImage
    {
        $contents = file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('File foto tidak dapat dibaca sebagai gambar.');
        }

        // Normalisasi orientasi dari EXIF (foto HP sering ter-rotate) supaya
        // watermark tidak menempel miring/terbalik. Hanya untuk JPEG ber-EXIF.
        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            $image = $this->applyExifOrientation($image, $file->getRealPath());
        }

        return $image;
    }

    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? null;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated !== null && $rotated !== false) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function drawWatermark(
        \GdImage $image,
        string $outletName,
        float $latitude,
        float $longitude,
        Carbon $takenAt
    ): void {
        $width = imagesx($image);
        $height = imagesy($image);

        // Skala ukuran font relatif terhadap lebar foto supaya konsisten di
        // foto resolusi tinggi maupun rendah.
        $fontSize = max(12, (int) round($width / 55));
        $lineHeight = (int) round($fontSize * 1.6);
        $padding = (int) round($fontSize * 0.8);

        $lines = [
            $outletName,
            $takenAt->format('d/m/Y H:i:s').' WIB',
            'GPS: '.number_format($latitude, 6, '.', '').', '.number_format($longitude, 6, '.', ''),
        ];

        $font = $this->resolveFont(self::FONT_PATH);
        $fontBold = $this->resolveFont(self::FONT_PATH_BOLD);

        // Hitung tinggi blok watermark untuk menggambar panel gelap
        // semi-transparan sebagai latar (biar teks kebaca di foto terang
        // maupun gelap).
        $blockHeight = ($lineHeight * count($lines)) + ($padding * 2);
        $blockTop = $height - $blockHeight;

        $panel = imagecolorallocatealpha($image, 0, 0, 0, 75);
        imagefilledrectangle($image, 0, $blockTop, $width, $height, $panel);

        $white = imagecolorallocate($image, 255, 255, 255);
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 40);

        $y = $blockTop + $padding + $fontSize;
        foreach ($lines as $index => $line) {
            $activeFont = $index === 0 ? $fontBold : $font;
            $x = $padding;

            // Bayangan tipis 1px agar tetap terbaca kalau font gagal (fallback
            // ke bitmap di bawah menangani kasus font TTF tak ada).
            if ($activeFont !== null) {
                imagettftext($image, $fontSize, 0, $x + 1, $y + 1, $shadow, $activeFont, $line);
                imagettftext($image, $fontSize, 0, $x, $y, $white, $activeFont, $line);
            } else {
                // Fallback tanpa TTF: pakai font bitmap bawaan GD (ukuran
                // tetap, kurang rapi tapi tetap jalan).
                imagestring($image, 5, $x, $y - $fontSize, $line, $white);
            }

            $y += $lineHeight;
        }
    }

    /**
     * Kembalikan path absolut font TTF kalau ada & GD mendukung FreeType,
     * atau null (memicu fallback bitmap).
     */
    private function resolveFont(string $relativePath): ?string
    {
        if (! function_exists('imagettftext')) {
            return null;
        }

        $absolute = base_path($relativePath);

        return is_file($absolute) ? $absolute : null;
    }

    private function saveJpeg(\GdImage $image, string $path): void
    {
        ob_start();
        imagejpeg($image, null, 85);
        $binary = ob_get_clean();

        Storage::disk('public')->put($path, $binary);
    }

    private function generateFilename(): string
    {
        return date('Ymd_His').'_'.bin2hex(random_bytes(8)).'.jpg';
    }
}
