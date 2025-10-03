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
        Schema::table('pool_plans', function (Blueprint $table) {
            $table->string('chargebee_plan_id')->nullable()->after('currency_code');
            $table->boolean('is_chargebee_synced')->default(false)->after('chargebee_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_plans', function (Blueprint $table) {
            $table->dropColumn(['chargebee_plan_id', 'is_chargebee_synced']);
        });
    }
};
