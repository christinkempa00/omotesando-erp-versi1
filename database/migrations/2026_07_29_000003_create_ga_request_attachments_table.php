<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ga_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ga_request_id')->constrained('ga_requests')->cascadeOnDelete();
            $table->string('photo_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga_request_attachments');
    }
};
