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
        Schema::create('onboardings', function (Blueprint $table) {
            $table->id();

            // Profile Section
            $table->integer('user_id'); 
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('role')->nullable();


            // Company Section
            $table->string('company_name')->nullable();
            $table->string('website')->nullable();
            $table->string('company_size')->nullable();  // e.g., "1–5", "6–50"
            $table->string('inboxes_tested_last_month')->nullable(); // e.g., "0–20"
            $table->string('monthly_spend')->nullable(); // e.g., "100K – $1M"

            $table->timestamps();
        });
    } 

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboardings');
    }
};
