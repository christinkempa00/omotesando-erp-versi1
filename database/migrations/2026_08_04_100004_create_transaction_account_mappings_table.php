<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supaya akun tujuan jurnal otomatis (lihat JournalPoster) tidak
     * di-hardcode di kode — bisa diubah Admin/Finance lewat halaman admin.
     */
    public function up(): void
    {
        Schema::create('transaction_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type')->unique();
            $table->foreignId('debit_account_id')->constrained('chart_of_accounts');
            $table->foreignId('credit_account_id')->constrained('chart_of_accounts');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_account_mappings');
    }
};
