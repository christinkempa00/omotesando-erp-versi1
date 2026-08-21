<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uniform_records', function (Blueprint $table) {
            // Tanda tangan pihak GA — pelengkap signature_path (karyawan,
            // "Diterima Oleh" saat serah terima) & return_signature_path
            // (karyawan, "Diserahkan Oleh" saat pengembalian). Sebelum ini
            // GA cuma tercatat nama teks tanpa tanda tangan sungguhan.
            $table->string('issued_by_signature_path')->nullable()->after('signature_path');
            $table->string('received_by_signature_path')->nullable()->after('return_signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('uniform_records', function (Blueprint $table) {
            $table->dropColumn(['issued_by_signature_path', 'received_by_signature_path']);
        });
    }
};
