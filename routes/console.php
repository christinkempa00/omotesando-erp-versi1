<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat Telegram Jadwal Pemeliharaan (H-1 hari & H-1 jam) — lihat
// App\Console\Commands\SendMaintenanceReminders. Tiap 15 menit supaya
// pengingat H-1 jam (jendela 1 jam) tidak pernah terlewat di antara run.
// SYARAT SERVER: scheduler ini cuma jalan kalau cron `php artisan
// schedule:run` sudah didaftarkan tiap menit di level OS — lihat DEPLOY.md.
Schedule::command('maintenance:send-reminders')->everyFifteenMinutes();
