<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tier akses per halaman (view/edit) per user — granularitas per
     * HALAMAN (bukan per Module row), krn Seragam sendiri 2 halaman
     * terpisah (Stok, Record) yang butuh tier independen. Tidak ada baris
     * di sini = default 'edit' (lihat User::canEdit()) supaya semua user
     * existing tetap persis seperti sebelumnya — zero regression.
     */
    public function up(): void
    {
        Schema::create('user_page_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('page_key');
            $table->string('access_level')->default('edit');

            $table->timestamps();

            $table->unique(['user_id', 'page_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_page_permissions');
    }
};
