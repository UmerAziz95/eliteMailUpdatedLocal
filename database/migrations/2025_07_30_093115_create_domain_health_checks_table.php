<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDomainHealthChecksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('domain_health_checks', function (Blueprint $table) {
            $table->id();

            // Foreign key to orders table (not nullable)
            $table->unsignedBigInteger('order_id')->index();

            // Domain being checked (nullable)
            $table->string('domain')->nullable();

            // Status summary fields (nullable)
            $table->string('status')->nullable();
            $table->string('summary')->nullable();

            // DNS section (nullable)
            $table->string('dns_status')->nullable();
            $table->json('dns_errors')->nullable();

            // Blacklist section (nullable)
            $table->boolean('blacklist_listed')->nullable();
            $table->json('blacklist_listed_on')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign key constraint (assuming orders table exists)
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('domain_health_checks');
    }
}