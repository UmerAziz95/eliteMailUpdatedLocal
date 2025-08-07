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
        Schema::create('panel_reassignment_history', function (Blueprint $table) {
            $table->id();
            
            // Mandatory fields
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_panel_id'); // The order panel that was affected
            $table->unsignedBigInteger('from_panel_id')->nullable(); // Source panel (null for new assignments)
            $table->unsignedBigInteger('to_panel_id')->nullable(); // Destination panel (null for removals)
            $table->unsignedBigInteger('reassigned_by'); // User who performed the reassignment
            $table->timestamp('reassignment_date');
            
            // Status and task assignment fields
            $table->enum('status', ['pending', 'completed', 'in-progress'])->default('pending');
            $table->unsignedBigInteger('assigned_to')->nullable(); // User assigned to handle the task
            
            // Action type to differentiate between removal and addition
            $table->enum('action_type', ['removed', 'added']); // Track removal from old panel and addition to new panel
            
            // Additional relevant information
            $table->decimal('space_transferred', 10, 2)->nullable(); // Amount of space moved
            $table->integer('splits_count')->default(0); // Number of splits involved
            $table->json('split_ids')->nullable(); // Array of split IDs that were moved
            $table->text('reason')->nullable(); // Reason for reassignment
            $table->text('notes')->nullable(); // Additional notes
            
            // Tracking fields
            $table->timestamp('task_started_at')->nullable();
            $table->timestamp('task_completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('order_panel_id')->references('id')->on('order_panel')->onDelete('cascade');
            $table->foreign('from_panel_id')->references('id')->on('panels')->onDelete('set null');
            $table->foreign('to_panel_id')->references('id')->on('panels')->onDelete('set null');
            $table->foreign('reassigned_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index('order_id');
            $table->index('order_panel_id');
            $table->index(['from_panel_id', 'to_panel_id']);
            $table->index('reassignment_date');
            $table->index('status');
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panel_reassignment_history');
    }
};
