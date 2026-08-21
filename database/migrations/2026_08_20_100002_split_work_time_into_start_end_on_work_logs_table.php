<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('work_date');
            $table->time('end_time')->nullable()->after('start_time');
        });

        DB::statement('UPDATE work_logs SET start_time = work_time');

        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn('work_time');
        });
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->time('work_time')->nullable()->after('work_date');
        });

        DB::statement('UPDATE work_logs SET work_time = start_time');

        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
