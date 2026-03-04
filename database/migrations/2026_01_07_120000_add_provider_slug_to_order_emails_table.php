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
            // Add provider_slug column to track which SMTP provider this mailbox belongs to
            if (!Schema::hasColumn('order_emails', 'provider_slug')) {
                $table->string('provider_slug', 50)->nullable()->after('domain');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_emails', function (Blueprint $table) {
            if (Schema::hasColumn('order_emails', 'provider_slug')) {
                $table->dropColumn('provider_slug');
            }
        });
    }
};

