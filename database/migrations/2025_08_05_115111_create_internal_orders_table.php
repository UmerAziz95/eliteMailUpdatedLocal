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
        Schema::create('internal_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('chargebee_invoice_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status')->default('pending');

            $table->timestamp('timer_started_at')->nullable();
            $table->timestamp('timer_paused_at')->nullable();
            $table->integer('total_paused_seconds')->default(0);
            $table->timestamp('completed_at')->nullable();

            $table->string('status_manage_by_admin')->default('pending');

            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->longText('meta')->nullable()->collation('utf8mb4_bin');
            $table->string('chargebee_subscription_id')->nullable();
            $table->string('chargebee_customer_id')->nullable();
            $table->string('currency')->default('USD');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->timestamp('last_draft_notification_sent_at')->nullable();
            $table->string('reason')->nullable();

            // Indexes
            $table->index('user_id');
            $table->index('plan_id');
            $table->index('assigned_to');
            $table->index('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_orders');
    }
};
