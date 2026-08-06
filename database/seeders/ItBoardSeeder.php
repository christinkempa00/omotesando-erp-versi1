<?php

namespace Database\Seeders;

use App\Models\IT\ItBoard;
use App\Models\IT\ItTaskLabel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ItBoardSeeder extends Seeder
{
    public function run(): void
    {
        $itUser = Role::where('name', Role::IT)->first()?->users()->first();

        $board = ItBoard::firstOrCreate(
            ['name' => 'Pengembangan Sistem ERP'],
            [
                'description' => 'Papan kerja utama tim IT untuk mencatat bug fix, pengembangan fitur, dan pekerjaan lainnya.',
                'created_by' => $itUser?->id,
            ]
        );

        $columns = ['To Do', 'In Progress', 'Review', 'Done'];

        foreach ($columns as $order => $name) {
            $board->columns()->firstOrCreate(['name' => $name], ['order' => $order]);
        }

        $labels = [
            ['name' => 'Bug', 'color' => 'red'],
            ['name' => 'Fitur Baru', 'color' => 'blue'],
            ['name' => 'Peningkatan', 'color' => 'purple'],
            ['name' => 'Dokumentasi', 'color' => 'green'],
        ];

        foreach ($labels as $label) {
            ItTaskLabel::firstOrCreate(['name' => $label['name']], ['color' => $label['color']]);
        }
    }
}
