<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_reactivations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('chargebee_subscription_id')->nullable();

            // Status of the reactivation attempt
            $table->string('status')->default('pending'); // pending, success, failed

            // Store response or error message
            $table->text('message')->nullable();
            $table->json('data')->nullable(); // Store full response data if needed

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_reactivations');
    }
};
