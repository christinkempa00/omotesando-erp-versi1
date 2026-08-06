<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu Production Batch bisa menghasilkan beberapa produk berbeda
        // (mis. dari bahan yang sama, batch memproduksi "Roti Tawar" DAN
        // "Roti Manis") — tiap baris di sini yang nanti dapat label/QR sendiri
        // lewat batch_labels, bukan production_batches langsung.
        Schema::create('production_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('qty');
            $table->string('unit');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_items');
    }
};
