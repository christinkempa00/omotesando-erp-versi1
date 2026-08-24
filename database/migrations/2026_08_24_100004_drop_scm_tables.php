<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian dari teardown 24/08/2026 (evaluasi sistem menyeluruh) — modul SCM
 * dihapus total (belum dipakai, akses/kebutuhannya belum jelas). Lihat
 * README "Riwayat Perubahan" tanggal yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('discrepancy_reports');
        Schema::dropIfExists('delivery_receipts');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
        Schema::dropIfExists('batch_labels');
        Schema::dropIfExists('production_batch_items');
        Schema::dropIfExists('production_batches');
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_requests');
    }

    public function down(): void
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('division_id')->constrained('divisions');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('requested_by')->constrained('users');
            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('material_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_request_id')->constrained('material_requests')->cascadeOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('qty');
            $table->string('unit');
            $table->timestamps();
        });

        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->foreignId('material_request_id')->constrained('material_requests');
            $table->foreignId('produced_by')->constrained('users');
            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');
            $table->timestamp('produced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('production_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('qty');
            $table->string('unit');
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('batch_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_item_id')->constrained('production_batch_items')->cascadeOnDelete();
            $table->string('label_code')->unique();
            $table->date('expiry_date')->nullable();
            $table->text('qr_code');
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_code')->unique();
            $table->foreignId('from_branch_id')->constrained('branches');
            $table->foreignId('to_branch_id')->constrained('branches');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sent_photo_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['draft', 'sent', 'received', 'received_with_discrepancy'])->default('draft');
            $table->text('qr_code')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->foreignId('batch_label_id')->constrained('batch_labels');
            $table->unsignedInteger('qty_sent');
            $table->unsignedInteger('qty_received')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->foreignId('received_by')->constrained('users');
            $table->string('received_photo_path');
            $table->timestamp('received_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('discrepancy_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->foreignId('delivery_note_item_id')->constrained('delivery_note_items')->cascadeOnDelete();
            $table->unsignedInteger('qty_expected');
            $table->unsignedInteger('qty_received');
            $table->integer('qty_diff');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // stock_balances/stock_movements digeneralisasi jadi polymorphic
        // (stockable_type/stockable_id, dulu batch_label_id FK) oleh migrasi
        // 2026_08_02_100007 (sudah ikut dihapus bareng modul Purchasing) —
        // skema di bawah ini merekonstruksi bentuk AKHIRNYA (polymorphic),
        // bukan bentuk awal Fase 1 (batch_label_id FK).
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('stockable_type');
            $table->unsignedBigInteger('stockable_id');
            $table->integer('qty_on_hand')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'stockable_type', 'stockable_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('stockable_type');
            $table->unsignedBigInteger('stockable_id');
            $table->integer('qty_change');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['stockable_type', 'stockable_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }
};
