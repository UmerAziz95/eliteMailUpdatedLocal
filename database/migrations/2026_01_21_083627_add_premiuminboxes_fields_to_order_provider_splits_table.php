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
        Schema::table('order_provider_splits', function (Blueprint $table) {
            $table->string('external_order_id')->nullable()->after('provider_slug');
            $table->string('client_order_id')->nullable()->after('external_order_id');
            $table->string('order_status')->nullable()->after('client_order_id');
            $table->timestamp('webhook_received_at')->nullable()->after('order_status');
            
            $table->index('external_order_id');
            $table->index('client_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_provider_splits', function (Blueprint $table) {
            $table->dropIndex(['external_order_id']);
            $table->dropIndex(['client_order_id']);
            $table->dropColumn([
                'external_order_id',
                'client_order_id',
                'order_status',
                'webhook_received_at',
            ]);
        });
    }
};
