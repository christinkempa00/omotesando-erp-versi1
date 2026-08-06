<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->string('asset_code')->unique(); // format: AST-0001, auto-generate

            $table->string('name');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('vendor_contact')->nullable();

            $table->enum('status', [
                'operational',
                'maintenance',
                'critical',
                'warning',
                'monitoring',
                'disposed',
            ])->default('operational');

            $table->string('location')->nullable(); // lokasi fisik di dalam outlet
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('custodian_name')->nullable(); // staf penanggung jawab

            $table->text('description')->nullable();
            $table->string('image_path')->nullable();

            $table->string('sp3_number')->nullable();
            $table->string('po_number')->nullable();
            $table->date('receive_date')->nullable();

            $table->decimal('dimension_p', 10, 2)->nullable();
            $table->decimal('dimension_l', 10, 2)->nullable();
            $table->decimal('dimension_t', 10, 2)->nullable();

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('depreciation_value', 15, 2)->nullable();
            $table->string('serial_number_photo_path')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
