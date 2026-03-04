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
        Schema::table('order_emails', function (Blueprint $table) {
            if (!Schema::hasColumn('order_emails', 'batch_id')) {
                $table->integer('batch_id')->nullable()->after('order_split_id')->comment('Batch identifier for grouping emails (200 per batch)');
                $table->index('batch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_emails', function (Blueprint $table) {
            if (Schema::hasColumn('order_emails', 'batch_id')) {
                $table->dropIndex(['batch_id']);
                $table->dropColumn('batch_id');
            }
        });
    }
};
