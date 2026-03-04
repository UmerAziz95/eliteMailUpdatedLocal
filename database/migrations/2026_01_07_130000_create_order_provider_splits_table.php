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
        Schema::create('order_provider_splits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('provider_slug', 50); // mailin, mailrun, premiuminboxes
            $table->string('provider_name', 255); // Display name
            $table->decimal('split_percentage', 5, 2); // Percentage assigned to this provider
            $table->integer('domain_count')->default(0); // Number of domains assigned
            $table->json('domains')->nullable(); // Array of domain names assigned to this provider
            $table->integer('priority')->default(0); // Provider priority at time of split
            $table->timestamps();
            
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index('order_id');
            $table->index('provider_slug');
            $table->index(['order_id', 'provider_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_provider_splits');
    }
};

