<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_reactivations', function (Blueprint $table) {
            $table->timestamp('latest_invoice_start_date')->nullable()->after('status');
            $table->timestamp('latest_invoice_end_date')->nullable()->after('latest_invoice_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_reactivations', function (Blueprint $table) {
            $table->dropColumn(['latest_invoice_start_date', 'latest_invoice_end_date']);
        });
    }
};
