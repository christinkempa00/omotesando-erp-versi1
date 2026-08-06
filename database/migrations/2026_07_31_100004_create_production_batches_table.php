<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();

            // Format: 000X/[bulan romawi]/BATCH/[tahun]
            $table->string('batch_number')->unique();

            $table->foreignId('material_request_id')->constrained('material_requests');
            $table->foreignId('produced_by')->constrained('users');

            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');

            $table->timestamp('produced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
