<?php

namespace Database\Seeders;

use App\Models\GA\Asset;
use App\Models\GA\MaintenanceJob;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaintenanceJobSeeder extends Seeder
{
    public function run(): void
    {
        $asset = Asset::first();
        $user = User::first();

        if (! $asset || ! $user) {
            $this->command?->warn('MaintenanceJobSeeder dilewati: butuh minimal 1 asset & 1 user.');

            return;
        }

        $samples = [
            [
                'title' => 'Servis AC rutin bulanan',
                'type' => 'preventive',
                'priority' => 'normal',
                'status' => 'scheduled',
                'scheduled_date' => now()->addDays(3)->toDateString(),
                'scheduled_time' => '09:00',
                'pic_name' => 'Budi',
                'vendor_name' => 'CV Dingin Sejuk',
                'checklist' => [
                    ['text' => 'Cek tekanan freon', 'done' => false],
                    ['text' => 'Bersihkan filter', 'done' => false],
                    ['text' => 'Cek kebocoran', 'done' => false],
                ],
            ],
            [
                'title' => 'Perbaikan mesin espresso',
                'type' => 'corrective',
                'priority' => 'high',
                'status' => 'in_progress',
                'scheduled_date' => now()->subDay()->toDateString(),
                'scheduled_time' => '14:00',
                'pic_name' => 'Sari',
                'vendor_name' => 'Teknisi Kopi ID',
                'checklist' => [
                    ['text' => 'Ganti gasket group head', 'done' => true],
                    ['text' => 'Kalibrasi suhu boiler', 'done' => false],
                ],
            ],
            [
                'title' => 'Kalibrasi timbangan dapur',
                'type' => 'calibration',
                'priority' => 'normal',
                'status' => 'completed',
                'scheduled_date' => now()->subDays(5)->toDateString(),
                'scheduled_time' => '10:30',
                'pic_name' => 'Andi',
                'vendor_name' => 'Metrologi Jaya',
                'cost' => 350000,
                'checklist' => [
                    ['text' => 'Uji beban standar', 'done' => true],
                    ['text' => 'Sertifikat kalibrasi', 'done' => true],
                ],
                'completion_notes' => 'Timbangan akurat, sertifikat diterbitkan.',
                'completed_at' => now()->subDays(5)->setTime(11, 15),
            ],
        ];

        foreach ($samples as $data) {
            MaintenanceJob::create(array_merge($data, [
                'job_code' => MaintenanceJob::generateJobCode(),
                'asset_id' => $asset->id,
                'branch_id' => $asset->branch_id,
                'location' => $asset->location,
                'created_by' => $user->id,
            ]));
        }

        $this->command?->info('3 contoh pekerjaan pemeliharaan dibuat.');
    }
}
