<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase Requisition — diajukan Outlet utk bahan makanan (kategori
     * 'food' saja; barang umum langsung jadi PO oleh GA tanpa requisisi,
     * lihat purchase_orders.purchase_requisition_id), disetujui/ditolak
     * role Purchasing (1 step). Kalau approved, Purchasing baru bikin PO
     * sesungguhnya (pilih supplier + isi harga) dari requisisi ini — pola
     * sama persis dengan MaterialRequest -> ProductionBatch di modul SCM.
     */
    public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();

            // Format: 000X/[bulan romawi]/PR/[tahun], sama seperti dokumen lain.
            $table->string('requisition_number')->unique();

            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('requested_by')->constrained('users');
            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};
