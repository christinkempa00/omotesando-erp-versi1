<?php

namespace App\Console\Commands;

use App\Models\GA\MaintenanceJob;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Pengingat Telegram utk Jadwal Pemeliharaan — dijalankan berkala lewat
 * scheduler (lihat routes/console.php). Tiga jenis pengingat:
 *
 * - H-1 hari: scheduled_date jatuh besok — cuma terkirim SEKALI (ditandai
 *   h1_day_reminder_sent_at).
 * - H-1 jam: scheduled_date + scheduled_time jatuh dalam 1 jam ke depan
 *   (butuh scheduled_time terisi — kalau kosong, jadwal itu dilewati utk
 *   pengingat jenis ini saja, tetap dapat pengingat H-1 hari) — cuma
 *   terkirim SEKALI (ditandai h1_hour_reminder_sent_at).
 * - Terlambat: jadwal sudah lewat tanggal & belum selesai (pakai
 *   MaintenanceJob::isOverdue(), definisi yang sama dgn badge "Terlambat"
 *   di halaman lain) — terkirim ULANG tiap 15 menit (tiap kali command ini
 *   jalan) selama masih terlambat (ditandai last_overdue_reminder_sent_at).
 *
 * Jadwal berstatus completed/cancelled tidak diikutkan sama sekali.
 */
class SendMaintenanceReminders extends Command
{
    protected $signature = 'maintenance:send-reminders';

    protected $description = 'Kirim pengingat Telegram H-1 hari, H-1 jam, & keterlambatan utk Jadwal Pemeliharaan';

    public function handle(TelegramNotifier $telegram): int
    {
        $activeStatuses = [MaintenanceJob::STATUS_SCHEDULED, MaintenanceJob::STATUS_IN_PROGRESS];

        $daySent = $this->sendDayReminders($telegram, $activeStatuses);
        $hourSent = $this->sendHourReminders($telegram, $activeStatuses);
        $overdueSent = $this->sendOverdueReminders($telegram, $activeStatuses);

        $this->info("Pengingat H-1 hari terkirim: {$daySent}. Pengingat H-1 jam terkirim: {$hourSent}. Pengingat terlambat terkirim: {$overdueSent}.");

        return self::SUCCESS;
    }

    private function sendDayReminders(TelegramNotifier $telegram, array $activeStatuses): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $jobs = MaintenanceJob::with('asset', 'branch')
            ->whereIn('status', $activeStatuses)
            ->whereDate('scheduled_date', $tomorrow)
            ->whereNull('h1_day_reminder_sent_at')
            ->get();

        foreach ($jobs as $job) {
            $telegram->sendMessage($this->reminderText($job, 'H-1 Hari — Jadwal Pemeliharaan Besok'));
            $job->update(['h1_day_reminder_sent_at' => now()]);
        }

        return $jobs->count();
    }

    private function sendHourReminders(TelegramNotifier $telegram, array $activeStatuses): int
    {
        $now = Carbon::now();
        $inOneHour = $now->copy()->addHour();

        $jobs = MaintenanceJob::with('asset', 'branch')
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('scheduled_time')
            ->whereNull('h1_hour_reminder_sent_at')
            ->get()
            ->filter(function (MaintenanceJob $job) use ($now, $inOneHour) {
                $scheduledAt = Carbon::parse($job->scheduled_date->toDateString().' '.$job->scheduled_time);

                return $scheduledAt->between($now, $inOneHour);
            });

        foreach ($jobs as $job) {
            $telegram->sendMessage($this->reminderText($job, 'H-1 Jam — Jadwal Pemeliharaan Segera Dimulai'));
            $job->update(['h1_hour_reminder_sent_at' => now()]);
        }

        return $jobs->count();
    }

    /**
     * Jadwal yang sudah "isOverdue()" (lewat tanggal & belum selesai) dapat
     * pengingat ulang tiap 15 menit (mengikuti interval scheduler-nya
     * sendiri — lihat routes/console.php) selama masih terlambat — dicek
     * di level PHP pakai method model yang sama persis dgn badge
     * "Terlambat" di halaman lain, supaya definisinya tidak pernah nyimpang.
     */
    private function sendOverdueReminders(TelegramNotifier $telegram, array $activeStatuses): int
    {
        $cutoff = Carbon::now()->subMinutes(15);

        $jobs = MaintenanceJob::with('asset', 'branch')
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('scheduled_date')
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('last_overdue_reminder_sent_at')
                    ->orWhere('last_overdue_reminder_sent_at', '<=', $cutoff);
            })
            ->get()
            ->filter(fn (MaintenanceJob $job) => $job->isOverdue());

        foreach ($jobs as $job) {
            $days = $job->scheduled_date->diffInDays(Carbon::today());
            $heading = 'Jadwal Pemeliharaan Terlambat — '.$days.' hari';

            $telegram->sendMessage($this->reminderText($job, $heading));
            $job->update(['last_overdue_reminder_sent_at' => now()]);
        }

        return $jobs->count();
    }

    private function reminderText(MaintenanceJob $job, string $heading): string
    {
        $text = "*{$heading}*\n";
        $text .= "Kode: {$job->job_code}\n";
        $text .= "Pekerjaan: {$job->title}\n";
        $text .= 'Aset: '.($job->asset?->name ?: '-')."\n";
        $text .= 'Outlet: '.($job->branch?->name ?: '-')."\n";
        $text .= 'Tanggal: '.$job->scheduled_date->translatedFormat('l, d F Y');

        if ($job->scheduled_time) {
            $text .= ' '.$job->scheduled_time;
        }

        if ($job->pic_name) {
            $text .= "\nPIC: {$job->pic_name}";
        }

        return $text;
    }
}
