<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * branch_location_id (nullable) ditambah ke 7 tabel GA yang menyimpan
     * branch_id — nullOnDelete supaya riwayat data lama tidak ikut hilang
     * kalau suatu saat sebuah Cabang dihapus. uniform_movements SENGAJA
     * tidak disentuh (tidak punya branch_id sendiri, cuma turunan dari
     * uniform_stocks).
     */
    public function up(): void
    {
        $tables = ['assets', 'uniform_stocks', 'uniform_records', 'work_logs', 'maintenance_jobs', 'ga_requests', 'ga_quick_requests'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('branch_location_id')->nullable()->after('branch_id')
                    ->constrained('branch_locations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = ['assets', 'uniform_stocks', 'uniform_records', 'work_logs', 'maintenance_jobs', 'ga_requests', 'ga_quick_requests'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_location_id');
            });
        }
    }
};
