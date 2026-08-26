<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained('branches');
            $table->date('report_date');
            // opening/closing — divalidasi di StoreOutletReportRequest.
            $table->string('session', 20);
            $table->text('notes')->nullable();

            // Akun Outlet yang membuat laporan (role Outlet, branch_id-nya
            // dipakai untuk scoping — lihat OutletReportController).
            $table->foreignId('submitted_by')->constrained('users');

            $table->timestamps();

            // Satu outlet hanya boleh punya 1 laporan opening + 1 closing per
            // hari (cegah duplikat entri). Dokumentasi banyak foto ditampung
            // di outlet_report_photos (hasMany), bukan dengan menggandakan
            // baris laporan.
            $table->unique(['branch_id', 'report_date', 'session'], 'outlet_reports_unique_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_reports');
    }
};
