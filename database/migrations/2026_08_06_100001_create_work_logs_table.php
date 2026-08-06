<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();

            $table->date('work_date');
            $table->time('work_time');
            $table->string('category'); // Pemeriksaan/Perbaikan/Instalasi/Maintenance — divalidasi di StoreWorkLogRequest
            $table->foreignId('branch_id')->constrained('branches');

            $table->string('technician_in_charge');
            $table->string('technician_assist')->nullable();

            $table->text('work_detail');
            // Nullable — diisi belakangan begitu pekerjaan selesai (lihat
            // status "Selesai"/"Belum Ada Hasil" di index/show, dihitung dari
            // kosong-tidaknya kolom ini, bukan kolom status terpisah).
            $table->text('work_result')->nullable();
            $table->text('notes')->nullable();
            // Lampiran foto dipindah ke tabel work_log_attachments (boleh
            // lebih dari 1), lihat migrasi create_work_log_attachments_table.

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
