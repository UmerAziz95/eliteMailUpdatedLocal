<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('discord_settings')) {
            Schema::create('discord_settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_name');
                $table->text('setting_value')->nullable();
                $table->boolean('discord_message_cron')->default(false); // Optional: for cron settings
                $table->timestamps(); // Optional: for created_at and updated_at
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_settings');
    }
};

