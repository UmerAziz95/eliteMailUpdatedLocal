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
        Schema::table('plans', function (Blueprint $table) {
            // Drop the existing foreign key that points to plans table
            $table->dropForeign(['master_plan_id']);
            
            // Add the correct foreign key that points to master_plans table
            $table->foreign('master_plan_id')->references('id')->on('master_plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Drop the correct foreign key
            $table->dropForeign(['master_plan_id']);
            
            // Restore the old incorrect foreign key (for rollback purposes)
            $table->foreign('master_plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }
};
