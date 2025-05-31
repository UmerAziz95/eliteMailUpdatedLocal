<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPanelTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_panel', function (Blueprint $table) {
            $table->id();

            // Foreign key columns
            $table->unsignedBigInteger('panel_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('contractor_id')->nullable();

            // Additional metadata
            $table->decimal('space_assigned', 10, 2)->nullable();
            $table->string('status')->nullable(); // e.g., unallocated, allocated, rejected, inprogress, completed
            $table->longText('note')->nullable();

            // Timestamps for tracking lifecycle
            $table->timestamp('accepted_at')->nullable(); // when the panel accepted the order
            $table->timestamp('released_at')->nullable(); // when the panel released the order

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_panel');
    }
}
