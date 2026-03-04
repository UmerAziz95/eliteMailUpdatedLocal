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
        Schema::create('platform_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('platform_type'); // e.g., 'spaceship', 'namecheap', 'godaddy', etc.
            $table->json('credentials'); // Store all credentials as JSON for flexibility
            $table->timestamps();
            
            // Ensure one credential record per order per platform
            $table->unique(['order_id', 'platform_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_credentials');
    }
};



