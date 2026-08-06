<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uniform_stocks', function (Blueprint $table) {
            // Kondisi umum varian ini — sama kosakata dengan status Aset supaya
            // konsisten di seluruh modul GA: bagus, rusak, dalam_pemeliharaan.
            $table->enum('status', ['bagus', 'rusak', 'dalam_pemeliharaan'])
                ->default('bagus')
                ->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('uniform_stocks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
