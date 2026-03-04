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
            // Hosting Platform fields
            $table->string('hosting_platform')->nullable()->after('domains');
            
            // Dynamic hosting platform fields stored as JSON
            // This allows flexibility for different platforms with different required fields
            $table->json('hosting_platform_data')->nullable()->after('hosting_platform');
            
            // Alternatively, you can add specific columns for common fields
            // Uncomment these if you prefer individual columns over JSON
            // $table->string('hosting_platform_login')->nullable()->after('hosting_platform');
            // $table->string('hosting_platform_password')->nullable()->after('hosting_platform_login');
            // $table->text('hosting_platform_backup_codes')->nullable()->after('hosting_platform_password');
            // $table->string('hosting_platform_access_tutorial')->nullable()->after('hosting_platform_backup_codes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->dropColumn([
                'hosting_platform',
                'hosting_platform_data',
                // Uncomment if you added individual columns
                // 'hosting_platform_login',
                // 'hosting_platform_password',
                // 'hosting_platform_backup_codes',
                // 'hosting_platform_access_tutorial',
            ]);
        });
    }
};
