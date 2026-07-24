<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ga_requests', function (Blueprint $table) {
            $table->id();

            // Format: 000X/[bulan romawi]/GAR/[tahun], nomor urut kontinu per tahun
            $table->string('request_number')->unique();

            $table->foreignId('division_id')->constrained('divisions');
            $table->foreignId('branch_id')->constrained('branches');

            $table->enum('category', [
                'perbaikan_fasilitas',
                'infrastruktur_sistem',
                'keselamatan_kebersihan',
                'operasional_pengembangan',
                'maintenance_stock',
            ]);

            $table->text('description'); // kolom "Keterangan"

            $table->foreignId('requested_by')->constrained('users');

            $table->enum('status', [
                'draft',
                'submitted',
                'in_review',
                'approved',
                'rejected',
                'received',
            ])->default('draft');

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga_requests');
    }
};
