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
        Schema::create('pool_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pool_plan_id')->constrained()->onDelete('cascade');
            
            // ChargeBee related fields
            $table->string('chargebee_subscription_id')->unique()->nullable();
            $table->string('chargebee_customer_id')->nullable();
            $table->string('chargebee_invoice_id')->nullable();
            
            // Order details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'cancelled', 'failed'])->default('pending');
            $table->enum('status_manage_by_admin', ['warming', 'available', 'cancelled', 'completed'])->default('warming');
            
            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Additional data
            $table->json('meta')->nullable(); // Store ChargeBee response data
            $table->text('reason')->nullable(); // For cancellation or other reasons
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['status_manage_by_admin']);
            $table->index(['chargebee_subscription_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_orders');
    }
};
