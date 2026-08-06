<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ga_requests', function (Blueprint $table) {
            // Nama staf GA yang mengajukan, diisi manual di form (bukan
            // diambil otomatis dari akun login) — akun login tetap dipakai
            // untuk requested_by/kepemilikan data, ini murni nama tampilan.
            $table->string('requester_name')->nullable()->after('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('ga_requests', function (Blueprint $table) {
            $table->dropColumn('requester_name');
        });
    }
};
