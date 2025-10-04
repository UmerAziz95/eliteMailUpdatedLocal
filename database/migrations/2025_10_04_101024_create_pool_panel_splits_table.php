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
        Schema::create('pool_panel_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_panel_id')->constrained('pool_panels')->onDelete('cascade');
            $table->unsignedBigInteger('pool_id');
            $table->integer('inboxes_per_domain');
            $table->json('domains'); // Store domains as JSON array
            $table->string('uploaded_file_path')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['pool_panel_id']);
            $table->index(['pool_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_panel_splits');
    }
};
