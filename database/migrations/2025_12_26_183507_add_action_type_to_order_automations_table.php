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
        Schema::table('order_automations', function (Blueprint $table) {
            $table->string('action_type')->default('domain')->after('provider_type'); // 'domain' or 'mailbox'
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_automations', function (Blueprint $table) {
            $table->dropIndex(['action_type']);
            $table->dropColumn('action_type');
        });
    }
};
