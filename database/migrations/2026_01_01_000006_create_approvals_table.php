<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();

            // Polymorphic: bisa merujuk ke GaRequest, Transaction, PurchaseOrder, dll.
            $table->morphs('approvable'); // approvable_type, approvable_id

            $table->unsignedInteger('step'); // urutan approval ke berapa (1, 2, 3, ...)
            $table->string('approver_role'); // role yang berwenang approve di step ini
            $table->foreignId('approver_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete(); // diisi setelah benar-benar di-approve

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Satu approvable tidak boleh punya step ganda
            $table->unique(['approvable_type', 'approvable_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
