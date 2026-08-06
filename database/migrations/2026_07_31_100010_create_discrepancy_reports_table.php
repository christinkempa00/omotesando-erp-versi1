<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dibuat OTOMATIS oleh DeliveryNoteItemObserver saat qty_received !=
        // qty_sent — bukan form manual (lihat app/Observers/DeliveryNoteItemObserver.php).
        Schema::create('discrepancy_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->foreignId('delivery_note_item_id')->constrained('delivery_note_items')->cascadeOnDelete();
            $table->unsignedInteger('qty_expected');
            $table->unsignedInteger('qty_received');
            $table->integer('qty_diff'); // qty_received - qty_expected, bisa negatif (kurang) atau positif (lebih)
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discrepancy_reports');
    }
};
