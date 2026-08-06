<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log histori tiap perubahan stok (bukan cuma saldo akhir di
     * stock_balances) — supaya bisa ditelusuri asal setiap perubahan.
     * reference_type/reference_id polymorphic-style (FQCN + id, sama
     * seperti approvals.approvable_type) menunjuk ke model yang memicu
     * perubahan (BatchLabel = stok masuk dari produksi, DeliveryNote =
     * stok keluar saat kirim, DeliveryReceipt = stok masuk di outlet).
     * Insert-only log — sengaja tidak ada updated_at (lihat model).
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('batch_label_id')->constrained('batch_labels')->cascadeOnDelete();
            $table->integer('qty_change');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
