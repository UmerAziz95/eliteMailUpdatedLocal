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
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('timer_paused_at')->nullable()->after('timer_started_at');
            $table->integer('total_paused_seconds')->default(0)->after('timer_paused_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['timer_paused_at', 'total_paused_seconds']);
        });
    }
};
