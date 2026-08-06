<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();

            // Format: 000X/[bulan romawi]/PB/[tahun], sama seperti GaRequest::generateRequestNumber()
            $table->string('request_number')->unique();

            $table->foreignId('division_id')->constrained('divisions');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('requested_by')->constrained('users');

            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};
