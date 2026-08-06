<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ga_quick_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ga_quick_request_id')->constrained('ga_quick_requests')->cascadeOnDelete();

            $table->string('item_name');
            $table->unsignedInteger('qty')->default(1);
            $table->string('unit')->nullable();
            $table->string('notes')->nullable();
            $table->string('photo_link')->nullable(); // link/URL foto barang (bukan upload)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga_quick_request_items');
    }
};
