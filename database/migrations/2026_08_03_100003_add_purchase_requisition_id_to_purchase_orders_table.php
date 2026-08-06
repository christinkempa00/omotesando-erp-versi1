<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable — cuma diisi utk PO kategori 'food' (dibuat Purchasing dari
     * Purchase Requisition yang sudah approved). PO kategori 'general'
     * (dibuat GA langsung) tetap null di sini.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('purchase_requisition_id')->nullable()->after('supplier_id')
                ->constrained('purchase_requisitions');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['purchase_requisition_id']);
            $table->dropColumn('purchase_requisition_id');
        });
    }
};
