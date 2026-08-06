<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ga_request_items', function (Blueprint $table) {
            $table->string('unit')->default('Pcs')->after('item_name');
        });
    }

    public function down(): void
    {
        Schema::table('ga_request_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
