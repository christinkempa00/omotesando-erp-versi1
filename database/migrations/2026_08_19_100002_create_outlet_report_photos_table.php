<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_report_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('outlet_report_id')->constrained('outlet_reports')->cascadeOnDelete();
            // Path foto yang SUDAH diberi watermark (waktu server + outlet +
            // GPS) saat upload — lihat PhotoWatermarkService.
            $table->string('photo_path');

            // GPS device saat foto diambil. WAJIB terisi (submit diblokir
            // kalau izin lokasi ditolak — lihat form JS & Store request).
            // CATATAN: nilai ini berasal dari browser (navigator.geolocation)
            // dan BISA dipalsukan klien — disimpan sbg pelengkap, bukan bukti
            // anti-palsu. Yang terpercaya adalah taken_at (jam server) &
            // branch (terikat akun).
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Waktu server saat foto diterima — sumber waktu terpercaya
            // (bukan dari device), dipakai juga sbg teks watermark.
            $table->timestamp('taken_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_report_photos');
    }
};
