<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar area pemeriksaan (mis. Dapur, Toilet, Area Makan) PER-OUTLET —
     * dikelola GA (Fase B-1), prasyarat form laporan foto per area (Fase
     * B-2, belum dibangun — lihat OutletInspectionArea::hasBeenUsed()).
     */
    public function up(): void
    {
        Schema::create('outlet_inspection_areas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained('branches');
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_inspection_areas');
    }
};
