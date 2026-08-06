<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ga_requests', function (Blueprint $table) {
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal')->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('ga_requests', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
