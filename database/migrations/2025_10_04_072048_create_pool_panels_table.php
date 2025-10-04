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
        Schema::create('pool_panels', function (Blueprint $table) {
            $table->id();
            $table->string('auto_generated_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('limit')->default(0);
            $table->integer('remaining_limit')->default(0);
            $table->integer('used_limit')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index(['is_active']);
            $table->index(['remaining_limit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_panels');
    }
};
