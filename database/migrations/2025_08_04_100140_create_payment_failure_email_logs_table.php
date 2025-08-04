<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('payment_failure_email_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('payment_failure_id')->constrained()->onDelete('cascade');
        $table->date('sent_date'); // Only track the day, not time
        $table->timestamps();

        $table->unique(['payment_failure_id', 'sent_date']); // Prevent duplicates
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_failure_email_logs');
    }
};
