<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registry halaman/section GA & Head yang bisa ditandai "Dalam
     * Pemeliharaan" oleh role IT — dicek oleh CheckModuleMaintenance
     * middleware di routes/web.php. Terpisah dari tabel `modules` (yang
     * dipakai Head utk aktif/nonaktifkan modul secara kasar) — ini lebih
     * granular per halaman & dikontrol IT, bukan Head.
     */
    public function up(): void
    {
        Schema::create('system_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('is_under_maintenance')->default(false);
            $table->text('maintenance_note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_modules');
    }
};
