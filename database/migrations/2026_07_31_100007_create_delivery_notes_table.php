<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();

            // Format: 000X/[bulan romawi]/SJ/[tahun]
            $table->string('delivery_code')->unique();

            $table->foreignId('from_branch_id')->constrained('branches');
            $table->foreignId('to_branch_id')->constrained('branches');

            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sent_photo_path')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->enum('status', ['draft', 'sent', 'received', 'received_with_discrepancy'])->default('draft');

            $table->text('qr_code')->nullable(); // SVG string, di-generate saat status jadi 'sent'

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
