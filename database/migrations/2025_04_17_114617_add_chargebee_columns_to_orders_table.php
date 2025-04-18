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
            //
            $table->string('chargebee_subscription_id')->nullable()->after('meta');
            $table->string('chargebee_customer_id')->nullable()->after('chargebee_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->dropColumn('chargebee_subscription_id');
            $table->dropColumn('chargebee_customer_id');
        });
    }
};
