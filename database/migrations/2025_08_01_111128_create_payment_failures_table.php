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
    Schema::create('payment_failures', function (Blueprint $table) {
    $table->id();
    $table->string('chargebee_customer_id')->nullable();
    $table->string('chargebee_subscription_id')->nullable();
    $table->string('type')->nullable();
    $table->string('status')->nullable();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->unsignedBigInteger('plan_id')->nullable();
    $table->timestamp('failed_at');
    $table->json('invoice_data')->nullable();
    $table->timestamps();
     });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_failures');
    }
};
