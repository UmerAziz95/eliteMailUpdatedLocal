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
        Schema::create('pool_order_migration_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pool_order_id');
            $table->unsignedBigInteger('user_id')->comment('Customer who owns the order');
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('Admin/staff assigned to handle this task');
            $table->enum('task_type', ['configuration', 'cancellation'])->comment('configuration: in-progress, cancellation: cancelled');
            $table->enum('status', ['pending', 'in-progress', 'completed', 'failed'])->default('pending');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable()->comment('Store additional task details');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('pool_order_id')->references('id')->on('pool_orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('pool_order_id');
            $table->index('user_id');
            $table->index('assigned_to');
            $table->index('task_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_order_migration_tasks');
    }
};
