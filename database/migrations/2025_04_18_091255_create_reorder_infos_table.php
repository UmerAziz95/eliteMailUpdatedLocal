<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reorder_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('forwarding_url');
            $table->string('hosting_platform');
            $table->text('backup_codes')->nullable();
            $table->string('platform_login');
            $table->string('platform_password');
            $table->text('domains');
            $table->string('sending_platform');
            $table->string('sequencer_login');
            $table->string('sequencer_password');
            $table->integer('total_inboxes');
            $table->integer('inboxes_per_domain');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('prefix_variant_1');
            $table->string('prefix_variant_2');
            $table->string('persona_password');
            $table->string('profile_picture_link')->nullable();
            $table->string('email_persona_password');
            $table->string('email_persona_picture_link')->nullable();
            $table->string('master_inbox_email')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('coupon_code')->nullable();
            $table->timestamps();
        });
        // Set auto-increment starting value
        DB::statement("ALTER TABLE reorder_infos AUTO_INCREMENT = 1000;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reorder_infos');
    }
};
