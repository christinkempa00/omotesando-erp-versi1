<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian dari teardown 24/08/2026 (evaluasi sistem menyeluruh) — approval
 * generik lintas modul (Approvable trait/Approval model) dihapus, GaRequest
 * disederhanakan jadi draft->submitted tanpa approval. Lihat README
 * "Riwayat Perubahan" tanggal yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('approvals');
    }

    public function down(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();

            $table->morphs('approvable');

            $table->unsignedInteger('step');
            $table->string('approver_role');
            $table->foreignId('approver_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('note')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->unique(['approvable_type', 'approvable_id', 'step']);
        });
    }
};
