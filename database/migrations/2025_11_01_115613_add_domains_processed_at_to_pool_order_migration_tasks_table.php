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
        Schema::table('pool_order_migration_tasks', function (Blueprint $table) {
            $table->timestamp('domains_processed_at')->nullable()->after('completed_at')->comment('When domains were processed to available status after cancellation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_order_migration_tasks', function (Blueprint $table) {
            $table->dropColumn('domains_processed_at');
        });
    }
};
