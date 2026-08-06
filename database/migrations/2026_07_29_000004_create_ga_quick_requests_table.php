<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ga_quick_requests', function (Blueprint $table) {
            $table->id();

            $table->date('requested_date');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('user_name'); // kolom "User" di form
            $table->string('pic_name'); // "Person in charge"
            $table->enum('urgency', ['low', 'medium', 'high'])->default('low');
            $table->text('needs_description')->nullable(); // "Penjelasan kebutuhan"

            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('sent_at')->nullable(); // diisi kalau berhasil dikirim ke Telegram

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga_quick_requests');
    }
};
