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
        Schema::table('internal_orders', function (Blueprint $table) {
            // Add columns from reorder_infos table
            $table->string('forwarding_url')->nullable()->after('reason');
            $table->string('hosting_platform')->nullable()->after('forwarding_url');
            $table->string('other_platform')->nullable()->after('hosting_platform');
            $table->string('bison_url')->nullable()->after('other_platform');
            $table->string('bison_workspace')->nullable()->after('bison_url');
            $table->text('backup_codes')->nullable()->after('bison_workspace');
            $table->string('platform_login')->nullable()->after('backup_codes');
            $table->string('platform_password')->nullable()->after('platform_login');
            $table->text('domains')->nullable()->after('platform_password');
            $table->string('sending_platform')->nullable()->after('domains');
            $table->string('sequencer_login')->nullable()->after('sending_platform');
            $table->string('sequencer_password')->nullable()->after('sequencer_login');
            $table->integer('total_inboxes')->nullable()->after('sequencer_password');
            $table->integer('initial_total_inboxes')->default(0)->after('total_inboxes')->comment('Initial total inboxes at the time of order');
            $table->integer('inboxes_per_domain')->nullable()->after('initial_total_inboxes');
            $table->string('first_name')->nullable()->after('inboxes_per_domain');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('prefix_variant_1')->nullable()->after('last_name');
            $table->string('prefix_variant_2')->nullable()->after('prefix_variant_1');
            $table->json('prefix_variants')->nullable()->after('prefix_variant_2');
            $table->json('prefix_variants_details')->nullable()->after('prefix_variants');
            $table->string('persona_password')->nullable()->after('prefix_variants_details');
            $table->string('profile_picture_link')->nullable()->after('persona_password');
            $table->string('email_persona_password')->nullable()->after('profile_picture_link');
            $table->string('email_persona_picture_link')->nullable()->after('email_persona_password');
            $table->string('master_inbox_email')->nullable()->after('email_persona_picture_link');
            $table->text('additional_info')->nullable()->after('master_inbox_email');
            $table->string('coupon_code')->nullable()->after('additional_info');
            $table->string('tutorial_section')->nullable()->after('coupon_code');
            
            // Add indexes for better performance
            $table->index('hosting_platform');
            $table->index('sending_platform');
            $table->index('total_inboxes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internal_orders', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['hosting_platform']);
            $table->dropIndex(['sending_platform']);
            $table->dropIndex(['total_inboxes']);
            
            // Drop all added columns
            $table->dropColumn([
                'forwarding_url',
                'hosting_platform',
                'other_platform',
                'bison_url',
                'bison_workspace',
                'backup_codes',
                'platform_login',
                'platform_password',
                'domains',
                'sending_platform',
                'sequencer_login',
                'sequencer_password',
                'total_inboxes',
                'initial_total_inboxes',
                'inboxes_per_domain',
                'first_name',
                'last_name',
                'prefix_variant_1',
                'prefix_variant_2',
                'prefix_variants',
                'prefix_variants_details',
                'persona_password',
                'profile_picture_link',
                'email_persona_password',
                'email_persona_picture_link',
                'master_inbox_email',
                'additional_info',
                'coupon_code',
                'tutorial_section'
            ]);
        });
    }
};
