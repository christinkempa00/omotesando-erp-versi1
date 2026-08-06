<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saldo stok real-time per lokasi (branch) x batch label — diperbarui
     * OTOMATIS lewat StockLedger service yang dipanggil dari observer
     * (BatchLabelObserver, DeliveryNoteObserver, DeliveryReceiptObserver),
     * bukan diisi manual. Lihat stock_movements utk histori tiap perubahan.
     */
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('batch_label_id')->constrained('batch_labels')->cascadeOnDelete();
            $table->integer('qty_on_hand')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'batch_label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
