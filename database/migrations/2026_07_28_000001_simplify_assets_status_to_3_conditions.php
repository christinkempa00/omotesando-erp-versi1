<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ALTER TABLE ... MODIFY dan ENUM adalah sintaks khusus MySQL.
        // Di driver lain (mis. SQLite yang dipakai saat testing), kolom
        // bertipe dinamis dan tidak perlu diubah tipenya secara eksplisit.
        if (DB::getDriverName() === 'mysql') {
            // Longgarkan dulu jadi VARCHAR supaya nilai status baru bisa ditulis
            // (ENUM lama akan menolak/memotong nilai yang belum terdaftar di situ).
            DB::statement('ALTER TABLE assets MODIFY status VARCHAR(30) NOT NULL DEFAULT \'operational\'');
        }

        DB::table('assets')->whereIn('status', ['operational', 'monitoring'])->update(['status' => 'bagus']);
        DB::table('assets')->whereIn('status', ['maintenance', 'warning'])->update(['status' => 'dalam_pemeliharaan']);
        DB::table('assets')->whereIn('status', ['critical', 'disposed'])->update(['status' => 'rusak']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE assets MODIFY status ENUM('bagus', 'rusak', 'dalam_pemeliharaan') NOT NULL DEFAULT 'bagus'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE assets MODIFY status VARCHAR(30) NOT NULL DEFAULT \'bagus\'');
        }

        DB::table('assets')->where('status', 'bagus')->update(['status' => 'operational']);
        DB::table('assets')->where('status', 'dalam_pemeliharaan')->update(['status' => 'maintenance']);
        DB::table('assets')->where('status', 'rusak')->update(['status' => 'critical']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE assets MODIFY status ENUM('operational', 'maintenance', 'critical', 'warning', 'monitoring', 'disposed') NOT NULL DEFAULT 'operational'");
        }
    }
};