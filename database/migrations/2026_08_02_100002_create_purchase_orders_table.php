<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * category menentukan siapa yang boleh membuat PO ini (Cost Control utk
     * 'food', GA utk 'general') DAN apakah goods_receipt_items-nya ikut
     * memperbarui stock_balances/stock_movements — hanya 'food' yang
     * dilacak sebagai stok real-time (barang umum tidak pakai "batch" ala
     * produksi, lihat GoodsReceiptItemObserver).
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            // Format: 000X/[bulan romawi]/PO/[tahun], sama seperti dokumen lain.
            $table->string('po_number')->unique();

            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('ordered_by')->constrained('users');
            $table->enum('category', ['food', 'general']);
            $table->date('order_date');
            $table->enum('status', ['submitted', 'approved', 'rejected', 'received'])->default('submitted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
