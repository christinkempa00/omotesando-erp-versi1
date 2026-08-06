<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper tipis di atas Telegram Bot API (sendMessage).
 *
 * Belum ada bot token/chat id yang dikonfigurasi saat kelas ini dibuat —
 * sengaja dibuat "aman tanpa kredensial": kalau config belum diisi, kirim
 * pesan dianggap gagal (return false) dan dicatat di log, TIDAK melempar
 * exception, supaya fitur yang memanggilnya (mis. Quick Request GA) tetap
 * bisa dipakai (datanya tersimpan) walau pengiriman ke Telegram belum aktif.
 *
 * Cara mengaktifkan: isi TELEGRAM_BOT_TOKEN & TELEGRAM_CHAT_ID di .env
 * (sudah didaftarkan di config/services.php sebagai services.telegram.*).
 */
class TelegramNotifier
{
    public function isConfigured(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.chat_id'));
    }

    public function sendMessage(string $text): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('TelegramNotifier: pesan tidak dikirim karena bot token/chat id belum dikonfigurasi.', [
                'text' => $text,
            ]);

            return false;
        }

        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('TelegramNotifier: gagal mengirim pesan.', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Kirim dokumen (mis. PDF final yang sudah ditandatangani) via Telegram
     * Bot API sendDocument. $contents adalah isi file mentah (bukan path),
     * supaya pemanggil tidak perlu menulis file sementara ke disk.
     *
     * $chatId opsional — kalau diisi (mis. telegram_chat_id pribadi seorang
     * user), dokumen dikirim ke situ, BUKAN ke grup default
     * (services.telegram.chat_id). Dipakai utk kirim PDF final langsung ke
     * pemohon, terpisah dari notifikasi umum ke grup.
     */
    public function sendDocument(string $filename, string $contents, ?string $caption = null, ?string $chatId = null): bool
    {
        $targetChatId = $chatId ?? config('services.telegram.chat_id');

        if (! filled(config('services.telegram.bot_token')) || ! filled($targetChatId)) {
            Log::warning('TelegramNotifier: dokumen tidak dikirim karena bot token/chat id belum dikonfigurasi.', [
                'filename' => $filename,
            ]);

            return false;
        }

        $token = config('services.telegram.bot_token');

        try {
            $request = Http::attach('document', $contents, $filename);

            $response = $request->post("https://api.telegram.org/bot{$token}/sendDocument", array_filter([
                'chat_id' => $targetChatId,
                'caption' => $caption,
            ]));

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('TelegramNotifier: gagal mengirim dokumen.', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
