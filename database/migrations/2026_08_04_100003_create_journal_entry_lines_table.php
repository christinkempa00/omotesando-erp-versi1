<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris = satu akun tersentuh, dengan kolom debit & credit (salah
     * satu 0) — bukan tabel terpisah per jenis. Validasi "total debit =
     * total credit per journal_entry" dilakukan di level aplikasi (lihat
     * App\Services\Finance\JournalPoster), bukan DB constraint — MySQL
     * tidak punya cross-row CHECK constraint native tanpa trigger.
     */
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
