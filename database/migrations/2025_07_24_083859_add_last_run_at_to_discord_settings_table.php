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
            $table->timestamp('last_run_at')->nullable()->after('cron_occurrence');
        });
    }

    public function down()
    {
        Schema::table('discord_settings', function (Blueprint $table) {
            $table->dropColumn('last_run_at');
        });
    }
};
