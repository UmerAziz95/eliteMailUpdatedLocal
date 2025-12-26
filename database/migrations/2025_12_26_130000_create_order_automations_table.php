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
        Schema::create('order_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('provider_type')->nullable();
            $table->string('job_uuid')->unique();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('response_data')->nullable(); // Store full API response for debugging
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('job_uuid');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_automations');
    }
};

