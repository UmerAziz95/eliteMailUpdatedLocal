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
        Schema::create('pool_panel_reassignment_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pool_id');
            $table->unsignedBigInteger('pool_panel_id');
            $table->unsignedBigInteger('from_pool_panel_id')->nullable();
            $table->unsignedBigInteger('to_pool_panel_id')->nullable();
            $table->unsignedBigInteger('pool_panel_split_id')->nullable();
            $table->unsignedBigInteger('reassigned_by');
            $table->timestamp('reassignment_date');

            $table->enum('status', ['pending', 'completed', 'in-progress'])->default('pending');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->enum('action_type', ['removed', 'added']);

            $table->decimal('space_transferred', 10, 2)->nullable();
            $table->integer('splits_count')->default(0);
            $table->json('split_ids')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('task_started_at')->nullable();
            $table->timestamp('task_completed_at')->nullable();
            $table->text('completion_notes')->nullable();

            $table->timestamps();

            $table->foreign('pool_id')->references('id')->on('pools')->onDelete('cascade');
$table->foreign('pool_panel_id')->references('id')->on('pool_panels')->onDelete('cascade');
$table->foreign('from_pool_panel_id')->references('id')->on('pool_panels')->onDelete('set null');
$table->foreign('to_pool_panel_id')->references('id')->on('pool_panels')->onDelete('set null');
$table->foreign('pool_panel_split_id')->references('id')->on('pool_panel_splits')->onDelete('set null');
$table->foreign('reassigned_by')->references('id')->on('users')->onDelete('cascade');
$table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

$table->index('pool_id');
$table->index('pool_panel_id');
$table->index(['from_pool_panel_id', 'to_pool_panel_id'], 'pp_from_to_idx'); // ← FIXED
$table->index('pool_panel_split_id');
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
        Schema::dropIfExists('pool_panel_reassignment_history');
    }
};
