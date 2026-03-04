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
        // Update the status_manage_by_admin enum to include 'draft', 'pending', and 'in-progress'
        DB::statement("ALTER TABLE pool_orders MODIFY COLUMN status_manage_by_admin ENUM('draft', 'pending', 'in-progress', 'warming', 'available', 'cancelled', 'completed') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original enum values
        DB::statement("ALTER TABLE pool_orders MODIFY COLUMN status_manage_by_admin ENUM('warming', 'available', 'cancelled', 'completed') DEFAULT 'warming'");
    }
};
