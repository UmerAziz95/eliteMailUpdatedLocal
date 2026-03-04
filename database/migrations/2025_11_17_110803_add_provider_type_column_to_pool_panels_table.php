<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pool_panels', function (Blueprint $table) {
            $table->enum('provider_type', ['Google', 'Microsoft 365'])->default('Google')->after('created_by');
            $table->unsignedInteger('pool_panel_sr_no')->nullable()->after('provider_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_panels', function (Blueprint $table) {
            $table->dropColumn('provider_type');
            $table->dropColumn('pool_panel_sr_no');
        });
    }
};
