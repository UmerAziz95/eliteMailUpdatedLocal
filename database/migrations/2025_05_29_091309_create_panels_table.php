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
        Schema::create('panels', function (Blueprint $table) {
            $table->id();

            $table->string('auto_generated_id')->nullable(); // Can be filled via model event or UUID logic
            $table->string('title')->nullable();
            $table->string('description')->nullable();

            $table->integer('limit')->default(1790); // Total capacity
            $table->integer('remaining_limit')->default(1790); // Track real-time available space

            $table->boolean('is_active')->default(true); // Panel activation state

            $table->string('created_by')->nullable(); // Could store user ID or email

            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panels');
    }
};
