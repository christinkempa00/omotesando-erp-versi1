<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ga_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ga_request_id')->constrained('ga_requests')->cascadeOnDelete();

            $table->string('item_name');
            $table->unsignedInteger('qty');
            $table->decimal('price_per_unit', 15, 2);
            $table->decimal('total', 15, 2); // qty * price_per_unit
            $table->string('vendor_name')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga_request_items');
    }
};
