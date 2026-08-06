<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 1 mengunci stock_balances/stock_movements ke batch_label_id
     * (unit hasil produksi ber-QR). Fase 2 memperkenalkan sumber stok kedua
     * yang bukan "batch" produksi sama sekali: barang bahan makanan yang
     * dibeli langsung dari supplier (GoodsReceiptItem) — lihat Purchasing.
     * Digeneralisasi jadi polymorphic (stockable_type/stockable_id) supaya
     * satu ledger tetap jadi satu-satunya sumber kebenaran stok, dipakai
     * baik oleh BatchLabel maupun GoodsReceiptItem.
     */
    public function up(): void
    {
        // MySQL InnoDB menumpangkan FK stock_balances_branch_id_foreign ke
        // composite unique (branch_id, batch_label_id) sebagai index
        // pendukungnya (branch_id kolom pertama di situ) — index sementara
        // ini membebaskan composite unique itu utk di-drop (error 1553 tanpa
        // ini), lalu dihapus lagi di akhir karena composite unique yang baru
        // (branch_id, stockable_type, stockable_id) sudah cukup menggantikan.
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->index('branch_id', 'stock_balances_branch_id_temp_index');
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->dropForeign(['batch_label_id']);
            $table->dropUnique(['branch_id', 'batch_label_id']);
            $table->renameColumn('batch_label_id', 'stockable_id');
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->string('stockable_type')->after('branch_id');
            $table->unique(['branch_id', 'stockable_type', 'stockable_id']);
            $table->dropIndex('stock_balances_branch_id_temp_index');
        });

        // stock_movements tidak punya composite unique yang menumpangi
        // branch_id (cuma index reference_type/reference_id) — jadi tidak
        // butuh index sementara seperti stock_balances di atas.
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['batch_label_id']);
            $table->renameColumn('batch_label_id', 'stockable_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('stockable_type')->after('branch_id');
            $table->index(['stockable_type', 'stockable_id']);
        });

        // Backfill baris lama (kalau ada) supaya tetap konsisten — di Fase 1
        // semua baris yang sudah ada pasti berasal dari BatchLabel.
        DB::table('stock_balances')->update(['stockable_type' => \App\Models\SCM\BatchLabel::class]);
        DB::table('stock_movements')->update(['stockable_type' => \App\Models\SCM\BatchLabel::class]);
    }

    public function down(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->index('branch_id', 'stock_balances_branch_id_temp_index');
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'stockable_type', 'stockable_id']);
            $table->dropColumn('stockable_type');
            $table->renameColumn('stockable_id', 'batch_label_id');
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->foreign('batch_label_id')->references('id')->on('batch_labels')->cascadeOnDelete();
            $table->unique(['branch_id', 'batch_label_id']);
            $table->dropIndex('stock_balances_branch_id_temp_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['stockable_type', 'stockable_id']);
            $table->dropColumn('stockable_type');
            $table->renameColumn('stockable_id', 'batch_label_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('batch_label_id')->references('id')->on('batch_labels')->cascadeOnDelete();
        });
    }
};
