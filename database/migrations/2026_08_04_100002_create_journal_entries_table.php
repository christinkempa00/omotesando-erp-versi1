<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * reference_type/reference_id polymorphic-style (FQCN + id), sama
     * seperti Approval::approvable & StockMovement::reference — menunjuk ke
     * model yang memicu jurnal ini (GaRequest, GoodsReceipt, SupplierInvoice).
     * created_by nullable krn jurnal dibuat OTOMATIS lewat Observer
     * (App\Services\Finance\JournalPoster), bukan form manual.
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            // Format: 000X/[bulan romawi]/JE/[tahun], sama seperti dokumen lain.
            $table->string('entry_number')->unique();

            $table->date('entry_date');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
