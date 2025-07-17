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
        Schema::create('domain_removal_tasks', function (Blueprint $table) {
            $table->id();
            $table->dateTime('started_queue_date'); // Date when queue task should start (subscription end + 72 hours)
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id'); // Order ID for reference
            $table->string('chargebee_subscription_id');
            $table->text('reason')->nullable(); // Cancellation reason
            $table->unsignedBigInteger('assigned_to')->nullable(); // Admin/user assigned to handle this task
            $table->enum('status', ['pending', 'in-progress', 'completed', 'failed'])->default('pending');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for better performance
            $table->index(['status', 'started_queue_date']);
            $table->index('chargebee_subscription_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_removal_tasks');
    }
};
