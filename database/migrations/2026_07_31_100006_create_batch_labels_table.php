<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_item_id')->constrained('production_batch_items')->cascadeOnDelete();
            $table->string('label_code')->unique();
            $table->text('qr_code'); // SVG string QR, disimpan spy tidak perlu regenerate tiap cetak ulang
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_labels');
    }
};
