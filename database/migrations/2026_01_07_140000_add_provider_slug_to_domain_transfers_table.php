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
        Schema::table('domain_transfers', function (Blueprint $table) {
            // Add provider_slug column to identify which provider is used for domain transfer
            if (!Schema::hasColumn('domain_transfers', 'provider_slug')) {
                $table->string('provider_slug', 50)->nullable()->after('order_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('domain_transfers', 'provider_slug')) {
                $table->dropColumn('provider_slug');
            }
        });
    }
};

