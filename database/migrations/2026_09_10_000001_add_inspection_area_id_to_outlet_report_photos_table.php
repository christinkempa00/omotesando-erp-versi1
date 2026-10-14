<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlet_report_photos', function (Blueprint $table) {
            $table->foreignId('inspection_area_id')
                ->nullable()
                ->after('outlet_report_id')
                ->constrained('outlet_inspection_areas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('outlet_report_photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inspection_area_id');
        });
    }
};
