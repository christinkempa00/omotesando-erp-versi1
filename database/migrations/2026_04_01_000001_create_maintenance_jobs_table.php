<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_jobs', function (Blueprint $table) {
            $table->id();

            $table->string('job_code')->unique(); // format: MNT-2026-0001, reset per tahun

            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('title');

            $table->enum('type', [
                'preventive',
                'corrective',
                'cleaning_inspection',
                'calibration',
                'service_vendor',
                'predictive',
            ])->default('preventive');

            $table->enum('priority', [
                'emergency',
                'high',
                'normal',
            ])->default('normal');

            $table->enum('status', [
                'scheduled',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('scheduled');

            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();

            $table->string('pic_name')->nullable();     // penanggung jawab pekerjaan
            $table->string('vendor_name')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches');

            $table->decimal('cost', 15, 2)->nullable();

            $table->json('checklist')->nullable();        // [{text, done}]
            $table->text('notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_jobs');
    }
};
