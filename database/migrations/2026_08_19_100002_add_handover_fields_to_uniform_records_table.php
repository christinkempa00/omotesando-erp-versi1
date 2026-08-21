<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uniform_records', function (Blueprint $table) {
            // "Nama Penyerah" — staf GA yang menyerahkan (dulu cuma createdBy->name,
            // sekarang field teks bebas supaya bisa beda dari akun yang login).
            $table->string('issued_by_name')->nullable()->after('employee_name');

            // --- Field pengembalian tambahan (dokumen "Pemeriksaan Pengembalian
            //     Barang") — semua nullable karena berlaku batch-wide, bukan per-item,
            //     dan record lama (sebelum fitur ini) tidak akan mengisinya. ---
            $table->string('returned_by_name')->nullable()->after('return_notes');
            $table->string('received_by_name')->nullable()->after('returned_by_name');
            $table->string('return_signature_path')->nullable()->after('received_by_name');

            $table->boolean('qty_sesuai')->nullable()->after('return_signature_path');
            $table->string('qty_sesuai_notes')->nullable()->after('qty_sesuai');
            $table->boolean('spesifikasi_sesuai')->nullable()->after('qty_sesuai_notes');
            $table->string('spesifikasi_sesuai_notes')->nullable()->after('spesifikasi_sesuai');
            $table->boolean('kondisi_sesuai')->nullable()->after('spesifikasi_sesuai_notes');
            $table->string('kondisi_sesuai_notes')->nullable()->after('kondisi_sesuai');
        });
    }

    public function down(): void
    {
        Schema::table('uniform_records', function (Blueprint $table) {
            $table->dropColumn([
                'issued_by_name',
                'returned_by_name',
                'received_by_name',
                'return_signature_path',
                'qty_sesuai',
                'qty_sesuai_notes',
                'spesifikasi_sesuai',
                'spesifikasi_sesuai_notes',
                'kondisi_sesuai',
                'kondisi_sesuai_notes',
            ]);
        });
    }
};
