<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->foreignId('batch_label_id')->constrained('batch_labels');
            $table->unsignedInteger('qty_sent');
            $table->unsignedInteger('qty_received')->nullable(); // diisi Outlet saat terima
            // Nullable, opsional diisi Gudang saat kirim — bukan buat operasional
            // SCM sekarang, tapi supaya modul Finance nanti gampang tarik COGS
            // tanpa perlu migrasi ulang (lihat catatan di prompt awal).
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
    }
};
