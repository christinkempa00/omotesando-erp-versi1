<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->timestamp('h1_day_reminder_sent_at')->nullable()->after('completion_notes');
            $table->timestamp('h1_hour_reminder_sent_at')->nullable()->after('h1_day_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->dropColumn(['h1_day_reminder_sent_at', 'h1_hour_reminder_sent_at']);
        });
    }
};
