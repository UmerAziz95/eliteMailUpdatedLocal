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
        Schema::table('order_provider_splits', function (Blueprint $table) {
            // Metadata JSON: stores provider-specific data like Mailrun enrollment UUID
            $table->json('metadata')->nullable()->after('webhook_received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_provider_splits', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
