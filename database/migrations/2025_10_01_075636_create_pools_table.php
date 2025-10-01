<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pools', function (Blueprint $table) {
            $table->id();
            
            // Common columns
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained()->onDelete('set null');
            
            // Orders table columns
            $table->string('chargebee_invoice_id')->nullable();
            $table->string('chargebee_customer_id')->nullable();
            $table->string('chargebee_subscription_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->string('currency')->default('USD');
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->string('status_manage_by_admin')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('last_draft_notification_sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('timer_started_at')->nullable();
            $table->timestamp('timer_paused_at')->nullable();
            $table->integer('total_paused_seconds')->default(0);
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->string('internal_order_id')->nullable();
            $table->boolean('is_internal_order_assignment')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->json('helpers_ids')->nullable();
            $table->text('shared_note')->nullable();
            $table->text('reassignment_note')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            
            // ReorderInfo table columns
            $table->string('forwarding_url')->nullable();
            $table->string('hosting_platform')->nullable();
            $table->string('other_platform')->nullable();
            $table->string('bison_url')->nullable();
            $table->string('bison_workspace')->nullable();
            $table->text('backup_codes')->nullable();
            $table->string('platform_login')->nullable();
            $table->string('platform_password')->nullable();
            $table->json('domains')->nullable();
            $table->string('sending_platform')->nullable();
            $table->string('sequencer_login')->nullable();
            $table->string('sequencer_password')->nullable();
            $table->integer('total_inboxes')->nullable();
            $table->integer('inboxes_per_domain')->nullable();
            $table->integer('initial_total_inboxes')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('prefix_variant_1')->nullable();
            $table->string('prefix_variant_2')->nullable();
            $table->json('prefix_variants')->nullable();
            $table->json('prefix_variants_details')->nullable();
            $table->string('persona_password')->nullable();
            $table->string('profile_picture_link')->nullable();
            $table->string('email_persona_password')->nullable();
            $table->string('email_persona_picture_link')->nullable();
            $table->string('master_inbox_email')->nullable();
            $table->boolean('master_inbox_confirmation')->default(false);
            $table->text('additional_info')->nullable();
            $table->string('coupon_code')->nullable();
            
            $table->timestamps();
            
            // Add indexes for better query performance
            $table->index('user_id');
            $table->index('plan_id');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('is_internal');
            $table->index('is_shared');
            $table->index('hosting_platform');
            $table->index('sending_platform');
            $table->index('master_inbox_email');
            $table->index('created_at');
            $table->index('completed_at');
            $table->index('timer_started_at');
            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'created_at']);
        });
        
        // Set auto-increment starting value
        DB::statement("ALTER TABLE pools AUTO_INCREMENT = 1000;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pools');
    }
};
