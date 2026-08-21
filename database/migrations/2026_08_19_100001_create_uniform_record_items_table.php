<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uniform_record_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('uniform_record_id')->constrained('uniform_records')->cascadeOnDelete();

            // Nullable & nullOnDelete — pola sama dgn uniform_records.uniform_stock_id,
            // supaya riwayat serah-terima tidak ikut hilang kalau varian stok terkait
            // nanti dihapus.
            $table->foreignId('uniform_stock_id')->nullable()->constrained('uniform_stocks')->nullOnDelete();

            // Denormalisasi varian (sesuai kondisi saat item ini diserahkan).
            $table->string('uniform_type'); // kolom "Nama Barang" di dokumen
            $table->string('size')->nullable();
            $table->string('color')->nullable(); // size+color digabung jadi "Spesifikasi" di tampilan/PDF

            $table->unsignedInteger('qty')->default(1);
            $table->string('item_condition')->nullable(); // kolom "Kondisi" di dok. serah-terima (teks bebas, mis. "Baru")
            $table->string('item_notes')->nullable(); // kolom "Keterangan"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uniform_record_items');
    }
};
