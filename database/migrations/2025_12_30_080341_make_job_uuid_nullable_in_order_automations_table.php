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
        // Check if unique constraint exists and drop it
        try {
            Schema::table('order_automations', function (Blueprint $table) {
                $table->dropUnique(['job_uuid']);
            });
        } catch (\Exception $e) {
            // Unique constraint might not exist or have different name, try alternative
            try {
                DB::statement('ALTER TABLE order_automations DROP INDEX order_automations_job_uuid_unique');
            } catch (\Exception $e2) {
                // Ignore if it doesn't exist
            }
        }
        
        // Drop existing index if it exists
        try {
            Schema::table('order_automations', function (Blueprint $table) {
                $table->dropIndex(['job_uuid']);
            });
        } catch (\Exception $e) {
            // Index might not exist, try alternative name
            try {
                DB::statement('ALTER TABLE order_automations DROP INDEX order_automations_job_uuid_index');
            } catch (\Exception $e2) {
                // Ignore if it doesn't exist
            }
        }
        
        // Change column to nullable
        DB::statement('ALTER TABLE order_automations MODIFY job_uuid VARCHAR(255) NULL');
        
        // Re-add index (without unique constraint)
        Schema::table('order_automations', function (Blueprint $table) {
            $table->index('job_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop index
        try {
            Schema::table('order_automations', function (Blueprint $table) {
                $table->dropIndex(['job_uuid']);
            });
        } catch (\Exception $e) {
            try {
                DB::statement('ALTER TABLE order_automations DROP INDEX order_automations_job_uuid_index');
            } catch (\Exception $e2) {
                // Ignore if it doesn't exist
            }
        }
        
        // Change column back to not nullable
        DB::statement('ALTER TABLE order_automations MODIFY job_uuid VARCHAR(255) NOT NULL');
        
        // Re-add unique constraint and index
        Schema::table('order_automations', function (Blueprint $table) {
            $table->unique('job_uuid');
            $table->index('job_uuid'); // Re-add index as in original migration
        });
    }
};
