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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // chargebee_invoice_id is a unique identifier for the invoice in Chargebee
            $table->string('chargebee_invoice_id')->unique();
            // customer_id from Chargebee
            $table->string('chargebee_customer_id')->nullable();
            // subscription_id from Chargebee
            $table->string('chargebee_subscription_id')->nullable();
            // user_id is a foreign key that references the users table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // plan_id
            $table->string('plan_id')->nullable();
            // order_id is a foreign key that references the orders table
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            // amount is the total amount of the invoice
            $table->decimal('amount', 10, 2)->nullable();
            // status indicates the current status of the invoice (e.g., paid, pending)
            $table->string('status')->default('pending');
            // paid_at
            $table->timestamp('paid_at')->nullable();
            // metadata is a JSON column to store additional information about the invoice
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
