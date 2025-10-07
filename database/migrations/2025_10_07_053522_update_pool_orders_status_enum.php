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
            // First, change the enum column to include all old and new values
            \DB::statement("ALTER TABLE pool_orders MODIFY COLUMN status_manage_by_admin ENUM('warming', 'available', 'pending', 'in-progress', 'completed', 'cancelled') DEFAULT 'pending'");
            
            // Update existing 'warming' status to 'pending' and 'available' to 'in-progress'
            \DB::statement("UPDATE pool_orders SET status_manage_by_admin = 'pending' WHERE status_manage_by_admin = 'warming'");
            \DB::statement("UPDATE pool_orders SET status_manage_by_admin = 'in-progress' WHERE status_manage_by_admin = 'available'");
            
            // Finally, change the enum column to only include new values
            \DB::statement("ALTER TABLE pool_orders MODIFY COLUMN status_manage_by_admin ENUM('pending', 'in-progress', 'completed', 'cancelled') DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_orders', function (Blueprint $table) {
            // Revert the enum column
            \DB::statement("ALTER TABLE pool_orders MODIFY COLUMN status_manage_by_admin ENUM('warming', 'available', 'cancelled', 'completed') DEFAULT 'warming'");
            
            // Revert the data
            \DB::statement("UPDATE pool_orders SET status_manage_by_admin = 'warming' WHERE status_manage_by_admin = 'pending'");
            \DB::statement("UPDATE pool_orders SET status_manage_by_admin = 'available' WHERE status_manage_by_admin = 'in-progress'");
        });
    }
};
