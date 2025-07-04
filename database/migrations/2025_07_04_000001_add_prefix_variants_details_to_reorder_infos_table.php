<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reorder_infos', function (Blueprint $table) {
            // Add prefix_variants_details JSON column to store detailed info for each variant
            $table->json('prefix_variants_details')->nullable()->after('prefix_variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reorder_infos', function (Blueprint $table) {
            $table->dropColumn('prefix_variants_details');
        });
    }
};
