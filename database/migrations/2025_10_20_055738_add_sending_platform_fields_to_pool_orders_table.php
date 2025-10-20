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
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->string('sending_platform')->nullable()->after('hosting_platform_data');
            $table->json('sending_platform_data')->nullable()->after('sending_platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->dropColumn(['sending_platform', 'sending_platform_data']);
        });
    }
};
