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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percentage'); // changed from enum
            $table->decimal('value', 10, 2);
            $table->integer('usage_limit')->nullable();
            $table->integer('used')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('expires_at')->nullable();
            
            $table->unsignedBigInteger('plan_id')->nullable(); // plan relation
           

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};


