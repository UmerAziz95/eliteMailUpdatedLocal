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
        Schema::table('domain_removal_tasks', function (Blueprint $table) {
            $table->timestamp('broadcasted_at')->nullable()->after('started_queue_date')
                  ->comment('Timestamp when the task was broadcasted via WebSocket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_removal_tasks', function (Blueprint $table) {
            $table->dropColumn('broadcasted_at');
        });
    }
};
