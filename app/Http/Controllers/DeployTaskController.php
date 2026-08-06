<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Route bootstrap darurat — dipakai SEKALI saat deploy pertama kali ke
 * hosting yang mungkin tidak punya akses SSH/terminal, supaya migrate &
 * storage:link tetap bisa dijalankan lewat browser biasa.
 *
 * Didaftarkan di routes/deploy.php lewat callback then: di bootstrap/app.php
 * — SENGAJA di luar middleware group 'web' (tidak ada session/cookie/CSRF),
 * karena route ini harus tetap jalan di database yang benar-benar kosong
 * (session/cache project ini pakai driver 'database', tabelnya belum ada
 * sebelum migrate pertama kali jalan).
 *
 * KEAMANAN:
 * - HANYA aktif kalau DEPLOY_TOKEN diisi di .env (lihat config/app.php
 *   'deploy_token'). Kosong/tidak diset -> 404 total, method apa pun.
 * - Token dibandingkan pakai hash_equals() (aman dari timing attack).
 * - GET cuma menampilkan halaman konfirmasi kosong (form submit), TIDAK
 *   menjalankan command apa pun — supaya link-preview bot (WhatsApp/
 *   Telegram/dll, yang otomatis fetch URL utk generate preview begitu link
 *   dikirim) tidak memicu migrate/storage:link tanpa sengaja SEBELUM Anda
 *   sendiri sempat klik. Command sungguhan baru jalan di method POST
 *   (lewat submit form di halaman konfirmasi itu).
 *
 * WAJIB DIHAPUS (atau DEPLOY_TOKEN dikosongkan) setelah dipakai. CATATAN
 * PENTING: salah satu command di bawah adalah config:cache — begitu itu
 * jalan, Laravel "membekukan" nilai DEPLOY_TOKEN ke bootstrap/cache/config.php
 * dan BERHENTI membaca .env lagi. Artinya sekadar mengosongkan DEPLOY_TOKEN
 * di .env SETELAH config:cache pernah jalan TIDAK LANGSUNG mematikan route
 * ini. Cara PALING PASTI: hapus registrasi route ini dari routes/deploy.php
 * lalu upload ulang — itu selalu manjur apa pun status cache. Detail lengkap
 * ada di DEPLOY.md.
 */
class DeployTaskController
{
    /**
     * @var array<string, array{0: string, 1: array<string, mixed>}>
     */
    private const COMMANDS = [
        'migrate --force' => ['migrate', ['--force' => true]],
        'storage:link' => ['storage:link', []],
        'config:cache' => ['config:cache', []],
        'route:cache' => ['route:cache', []],
        'view:cache' => ['view:cache', []],
    ];

    /**
     * GET — tampilkan halaman konfirmasi kosong saja, tidak eksekusi apa-apa.
     */
    public function confirm(string $token): Response
    {
        $this->authorizeOrAbort($token);

        $items = collect(array_keys(self::COMMANDS))
            ->map(fn (string $label) => '<li>'.e($label).'</li>')
            ->implode('');

        $html = <<<HTML
            <!doctype html>
            <html>
            <head><meta charset="utf-8"><title>Deploy Tasks</title></head>
            <body style="font-family: monospace; padding: 2rem; max-width: 40rem;">
                <p>Token valid. Command yang akan dijalankan (klik tombol utk benar-benar mulai):</p>
                <ul>{$items}</ul>
                <form method="POST">
                    <button type="submit" style="padding: 0.5rem 1rem; font-size: 1rem;">Jalankan Sekarang</button>
                </form>
            </body>
            </html>
            HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    /**
     * POST — eksekusi sungguhan, tampilkan output tiap command apa adanya
     * (plain text, bukan halaman cantik) supaya kelihatan jelas kalau ada
     * yang gagal.
     */
    public function run(string $token): Response
    {
        $this->authorizeOrAbort($token);

        $output = '';

        foreach (self::COMMANDS as $label => [$command, $params]) {
            $output .= "=== {$label} ===\n";

            try {
                $exitCode = Artisan::call($command, $params);
                $output .= Artisan::output();
                $output .= "(exit code: {$exitCode})\n\n";
            } catch (Throwable $e) {
                $output .= 'GAGAL: '.$e->getMessage()."\n\n";
            }
        }

        return response($output, 200)->header('Content-Type', 'text/plain');
    }

    private function authorizeOrAbort(string $token): void
    {
        $expected = config('app.deploy_token');

        if (blank($expected) || ! hash_equals((string) $expected, $token)) {
            abort(404);
        }
    }
}
