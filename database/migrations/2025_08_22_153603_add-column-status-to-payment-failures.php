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
        Schema::table('payment_failures', function (Blueprint $table) {
            $table->string('status')->nullable()->after('type'); 
            // `nullable()` is optional; remove if you want it required.
            // `after('id')` places it after the `id` column (adjust as needed).
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_failures', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
