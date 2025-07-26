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
        Schema::create('panel_capacity_alerts', function (Blueprint $table) {
            $table->id();
            $table->integer('threshold'); // The threshold that was breached (10000, 5000, etc.)
            $table->integer('capacity_when_sent'); // The actual capacity when notification was sent
            $table->timestamp('notification_sent_at'); // When the notification was sent
            $table->timestamps();
            
            // Add index for better query performance
            $table->index(['threshold', 'created_at']);
            $table->index(['capacity_when_sent', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panel_capacity_alerts');
    }
};
