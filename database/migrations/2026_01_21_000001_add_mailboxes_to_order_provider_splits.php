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
            // Mailboxes JSON: stores all mailbox data per domain
            // Structure: { "domain.com": { "prefix_variant_1": { "id": 1, "name": "...", "mailbox": "...", "password": "...", "status": "active" } } }
            $table->json('mailboxes')->nullable()->after('domains');

            // Domain statuses: tracks activation status per domain
            // Structure: { "domain.com": { "status": "active", "updated_at": "..." } }
            $table->json('domain_statuses')->nullable()->after('mailboxes');

            // Flag: true when ALL domains in this split are active
            $table->boolean('all_domains_active')->default(false)->after('domain_statuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_provider_splits', function (Blueprint $table) {
            $table->dropColumn(['mailboxes', 'domain_statuses', 'all_domains_active']);
        });
    }
};
