<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uniform_movements', function (Blueprint $table) {
            $table->id();

            $table->string('movement_code')->unique(); // format: MOV-2026-0001, reset per tahun

            $table->foreignId('uniform_stock_id')->constrained('uniform_stocks')->cascadeOnDelete();

            $table->enum('movement_type', [
                'restock',
                'issue',
                'return',
                'adjustment',
                'disposal',
            ]);

            $table->integer('quantity'); // signed: + utk restock/return, - utk issue/disposal, bebas utk adjustment
            $table->unsignedInteger('available_after');
            $table->unsignedInteger('unusable_after');

            $table->string('source_record_id')->nullable(); // record_code di uniform_records kalau otomatis dari issue/return

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uniform_movements');
    }
};
