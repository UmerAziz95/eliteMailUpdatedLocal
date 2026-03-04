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
            // Add provider_type column
            if (!Schema::hasColumn('order_emails', 'provider_type')) {
                $table->string('provider_type')->nullable()->after('batch_id');
            }
            
            // Add provisioned_at column
            if (!Schema::hasColumn('order_emails', 'provisioned_at')) {
                $table->timestamp('provisioned_at')->nullable()->after('provider_type');
            }
            
            // Add mailin_status column
            if (!Schema::hasColumn('order_emails', 'mailin_status')) {
                $table->string('mailin_status')->nullable()->after('provisioned_at');
            }
            
            // Add mailin_mailbox_id column (if not exists)
            if (!Schema::hasColumn('order_emails', 'mailin_mailbox_id')) {
                $table->unsignedBigInteger('mailin_mailbox_id')->nullable()->after('mailin_status');
            }
            
            // Add mailin_domain_id column (if not exists)
            if (!Schema::hasColumn('order_emails', 'mailin_domain_id')) {
                $table->unsignedBigInteger('mailin_domain_id')->nullable()->after('mailin_mailbox_id');
            }
            
            // Add is_migrated_to_mailin column
            if (!Schema::hasColumn('order_emails', 'is_migrated_to_mailin')) {
                $table->boolean('is_migrated_to_mailin')->default(false)->after('mailin_domain_id');
            }
            
            // Add mailin_ai_inbox_id column
            if (!Schema::hasColumn('order_emails', 'mailin_ai_inbox_id')) {
                $table->string('mailin_ai_inbox_id')->nullable()->after('is_migrated_to_mailin');
            }
            
            // Add domain column
            if (!Schema::hasColumn('order_emails', 'domain')) {
                $table->string('domain')->nullable()->after('mailin_ai_inbox_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_emails', function (Blueprint $table) {
            if (Schema::hasColumn('order_emails', 'domain')) {
                $table->dropColumn('domain');
            }
            if (Schema::hasColumn('order_emails', 'mailin_ai_inbox_id')) {
                $table->dropColumn('mailin_ai_inbox_id');
            }
            if (Schema::hasColumn('order_emails', 'is_migrated_to_mailin')) {
                $table->dropColumn('is_migrated_to_mailin');
            }
            if (Schema::hasColumn('order_emails', 'mailin_domain_id')) {
                $table->dropColumn('mailin_domain_id');
            }
            if (Schema::hasColumn('order_emails', 'mailin_mailbox_id')) {
                $table->dropColumn('mailin_mailbox_id');
            }
            if (Schema::hasColumn('order_emails', 'mailin_status')) {
                $table->dropColumn('mailin_status');
            }
            if (Schema::hasColumn('order_emails', 'provisioned_at')) {
                $table->dropColumn('provisioned_at');
            }
            if (Schema::hasColumn('order_emails', 'provider_type')) {
                $table->dropColumn('provider_type');
            }
        });
    }
};

