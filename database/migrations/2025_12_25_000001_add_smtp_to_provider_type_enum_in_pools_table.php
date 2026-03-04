<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the provider_type column to include 'SMTP' in the ENUM values
        // Using raw SQL because Laravel doesn't support modifying ENUM values directly
        DB::statement("ALTER TABLE pools MODIFY COLUMN provider_type ENUM('Google', 'Microsoft 365', 'SMTP') DEFAULT 'Google'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM values (note: this will fail if any rows have 'SMTP' value)
        DB::statement("ALTER TABLE pools MODIFY COLUMN provider_type ENUM('Google', 'Microsoft 365') DEFAULT 'Google'");
    }
};
