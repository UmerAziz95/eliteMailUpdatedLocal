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
        Schema::table('domain_transfers', function (Blueprint $table) {
            $table->string('domain_status')->nullable()->after('status')->comment('Domain status from Mailin.ai API (e.g., active, pending)');
            $table->string('name_server_status')->nullable()->after('domain_status')->comment('Name server status from Mailin.ai API (e.g., active, pending)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_transfers', function (Blueprint $table) {
            $table->dropColumn(['domain_status', 'name_server_status']);
        });
    }
};

