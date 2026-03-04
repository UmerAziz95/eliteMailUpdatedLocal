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
        Schema::create('smtp_provider_splits', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name (Mailin, Mailgun, Mailrun)
            $table->string('slug')->unique(); // Unique slug (mailin, mailgun, mailrun)
            $table->string('api_endpoint', 500)->nullable();
            $table->string('email', 255); // Email for authentication (required)
            $table->string('password', 255); // Password for authentication (required)
            $table->json('additional_config')->nullable(); // Any custom provider settings
            $table->decimal('split_percentage', 5, 2)->default(0.00); // % workload split (0–100)
            $table->integer('priority')->default(0); // Execution order
            $table->boolean('is_active')->default(true); // Enable/disable provider
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_provider_splits');
    }
};

