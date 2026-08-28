<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sub-lokasi fisik di dalam satu outlet (mis. "Ask for Patty" yang
     * punya beberapa titik lokasi berbeda) — opsional, kebanyakan outlet
     * tidak akan punya baris di sini sama sekali. Pola sama persis dengan
     * outlet_inspection_areas (branch_id + name + sort_order + is_active).
     */
    public function up(): void
    {
        Schema::create('branch_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained('branches');
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['branch_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_locations');
    }
};
