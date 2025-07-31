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
        Schema::create('panel_capacity_notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('threshold'); // The threshold point (0, 2000, 3000, 4000, 5000, 10000)
            $table->integer('current_capacity'); // Current capacity when notification was triggered
            $table->boolean('is_active')->default(true); // Whether this threshold is currently triggered
            $table->timestamp('last_triggered_at')->nullable(); // When this threshold was last triggered
            $table->timestamps();
            
            // Add unique constraint to ensure one row per threshold
            $table->unique('threshold');
            
            // Add indexes for better performance
            $table->index(['threshold', 'is_active']);
            $table->index('last_triggered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panel_capacity_notifications');
    }
};
