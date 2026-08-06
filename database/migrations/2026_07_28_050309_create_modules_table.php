<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registry modul GA (Request, Aset, Seragam, Jadwal Pemeliharaan, dst) —
     * dipakai Head untuk aktif/nonaktifkan modul & atur role apa saja yang
     * boleh mengaksesnya (lihat ModuleAccessMiddleware).
     */
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
