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
        Schema::create('order_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('hosted_page_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_exception')->nullable();
            $table->string('chargebee_invoice_id')->nullable();
            $table->string('chargebee_subscription_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->json('invoice_data')->nullable();
            $table->json('customer_data')->nullable();
            $table->json('subscription_data')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->json('response')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
            
            // Add foreign key constraints if needed
            $table->index('user_id');
            $table->index('hosted_page_id');
            $table->index('chargebee_invoice_id');
            $table->index('chargebee_subscription_id');
            $table->index('customer_id');
            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payment_logs');
    }
};
