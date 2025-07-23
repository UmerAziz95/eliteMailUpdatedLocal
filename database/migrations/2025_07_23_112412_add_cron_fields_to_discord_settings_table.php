<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('discord_settings', function (Blueprint $table) {
        $table->dateTime('cron_start_from')->nullable()->after('discord_message_cron');
        $table->enum('cron_occurrence', ['daily', 'weekly', 'monthly'])->nullable()->after('cron_start_from');
    });
}

public function down()
{
    Schema::table('discord_settings', function (Blueprint $table) {
        $table->dropColumn(['cron_start_from', 'cron_occurrence']);
    });
}

};
