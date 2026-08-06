<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uniform_records', function (Blueprint $table) {
            $table->id();

            $table->string('record_code')->unique(); // format: SRH-2026-0001, reset per tahun

            $table->string('employee_name');
            $table->foreignId('branch_id')->constrained('branches');

            // Denormalisasi varian seragam (sesuai kondisi saat diserahkan, tetap
            // tersimpan apa adanya walau stok terkait nanti diubah/dihapus).
            $table->string('uniform_type');
            $table->string('size')->nullable();
            $table->string('color')->nullable();

            // Dipakai untuk sinkronisasi otomatis ke uniform_stocks saat issue/return.
            // Nullable & set null saat stok dihapus supaya riwayat serah-terima tidak ikut hilang.
            $table->foreignId('uniform_stock_id')->nullable()->constrained('uniform_stocks')->nullOnDelete();

            $table->date('issue_date');
            $table->string('issue_photo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('issue_notes')->nullable();

            $table->enum('status', ['issued', 'returned'])->default('issued');
            $table->date('return_date')->nullable();
            $table->enum('return_condition', ['good', 'damaged', 'lost'])->nullable();
            $table->text('return_notes')->nullable();

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uniform_records');
    }
};
