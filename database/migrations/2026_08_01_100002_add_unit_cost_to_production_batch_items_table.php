<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Belum ada kolom biaya di manapun pada tahap produksi sebelum ini
     * (material_request_items / production_batch_items murni qty). Kolom
     * ini ditambahkan spesifik supaya Laporan Nilai Persediaan (Fase 1)
     * punya "biaya per unit dari batch terkait" yang diminta — nullable
     * karena Produksi tidak selalu tahu biaya persis saat input batch.
     */
    public function up(): void
    {
        Schema::table('production_batch_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('production_batch_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
