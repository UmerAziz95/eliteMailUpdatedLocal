<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'Private SMTP' to the existing ENUM
        DB::statement("ALTER TABLE orders MODIFY provider_type ENUM('Google', 'Microsoft 365', 'Private SMTP') NULL DEFAULT 'Google'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Convert ENUM to VARCHAR temporarily to allow value changes
        DB::statement("ALTER TABLE orders MODIFY provider_type VARCHAR(50) NULL");
        
        // Step 2: Convert any 'Private SMTP' values to 'Google' before reverting
        DB::statement("UPDATE orders SET provider_type = 'Google' WHERE provider_type = 'Private SMTP'");
        
        // Step 3: Convert back to ENUM with only Google and Microsoft 365
        DB::statement("ALTER TABLE orders MODIFY provider_type ENUM('Google', 'Microsoft 365') NULL DEFAULT 'Google'");
    }
};
