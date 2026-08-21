<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ga_requests', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('subtotal_amount');
            $table->decimal('pph_percent', 5, 2)->nullable()->after('discount_percent');
        });

        // Data lama belum kenal pph/diskon — subtotal = total_amount lama.
        DB::statement('UPDATE ga_requests SET subtotal_amount = total_amount');
    }

    public function down(): void
    {
        Schema::table('ga_requests', function (Blueprint $table) {
            $table->dropColumn(['subtotal_amount', 'discount_percent', 'pph_percent']);
        });
    }
};
