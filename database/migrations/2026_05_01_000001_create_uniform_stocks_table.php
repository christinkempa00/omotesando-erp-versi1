<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uniform_stocks', function (Blueprint $table) {
            $table->id();

            $table->string('stock_code')->unique(); // format: UNI-{OUTLET}-{TIPE}-{UKURAN}-{WARNA}

            $table->foreignId('branch_id')->constrained('branches');
            $table->string('uniform_type'); // mis. Vest, Kemeja
            $table->string('size')->nullable(); // S, M, L, XL, XXL
            $table->string('color')->nullable();

            $table->unsignedInteger('available_stock')->default(0);
            $table->unsignedInteger('unusable_stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->string('stock_photo_path')->nullable();

            $table->timestamps();

            $table->unique(['branch_id', 'uniform_type', 'size', 'color'], 'uniform_stocks_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uniform_stocks');
    }
};
