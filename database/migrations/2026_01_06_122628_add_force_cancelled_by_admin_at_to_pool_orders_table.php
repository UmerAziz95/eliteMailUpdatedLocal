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
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->timestamp('force_cancelled_by_admin_at')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->dropColumn('force_cancelled_by_admin_at');
        });
    }
};
