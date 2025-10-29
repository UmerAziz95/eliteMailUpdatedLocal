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
            $table->string('pricing_model')->default('per_unit')->after('duration'); // flat or per_unit
            $table->string('billing_cycle')->default('1')->after('pricing_model'); // 1-10 or unlimited
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_plans', function (Blueprint $table) {
            $table->dropColumn(['pricing_model', 'billing_cycle']);
        });
    }
};
