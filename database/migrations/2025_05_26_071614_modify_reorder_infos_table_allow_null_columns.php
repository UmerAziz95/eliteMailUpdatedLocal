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
        Schema::table('reorder_infos', function (Blueprint $table) {
            // Modify columns to allow null values
            $table->string('forwarding_url')->nullable()->change();
            $table->string('hosting_platform')->nullable()->change();
            $table->string('platform_login')->nullable()->change();
            $table->string('platform_password')->nullable()->change();
            $table->text('domains')->nullable()->change();
            $table->string('sending_platform')->nullable()->change();
            $table->string('sequencer_login')->nullable()->change();
            $table->string('sequencer_password')->nullable()->change();
            $table->integer('inboxes_per_domain')->nullable()->change();
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('prefix_variant_1')->nullable()->change();
            $table->string('prefix_variant_2')->nullable()->change();
            $table->string('persona_password')->nullable()->change();
            $table->string('email_persona_password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reorder_infos', function (Blueprint $table) {
            // Revert columns back to not nullable
            $table->string('forwarding_url')->nullable(false)->change();
            $table->string('hosting_platform')->nullable(false)->change();
            $table->string('platform_login')->nullable(false)->change();
            $table->string('platform_password')->nullable(false)->change();
            $table->text('domains')->nullable(false)->change();
            $table->string('sending_platform')->nullable(false)->change();
            $table->string('sequencer_login')->nullable(false)->change();
            $table->string('sequencer_password')->nullable(false)->change();
            $table->integer('inboxes_per_domain')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('prefix_variant_1')->nullable(false)->change();
            $table->string('prefix_variant_2')->nullable(false)->change();
            $table->string('persona_password')->nullable(false)->change();
            $table->string('email_persona_password')->nullable(false)->change();
        });
    }
};
