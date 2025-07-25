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
        Schema::table('discord_settings', function (Blueprint $table) {
            $table->uuid('url_string')->nullable()->after('cron_occurrence'); // or after any relevant column
            $table->string('embedded_url')->nullable()->after('url_string');
        });
    }

    public function down(): void
    {
        Schema::table('discord_settings', function (Blueprint $table) {
            $table->dropColumn(['url_string', 'embedded_url']);
        });
    }
};
