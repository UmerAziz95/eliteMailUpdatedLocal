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
        Schema::table('panels', function (Blueprint $table) {
            // Provider type for the panel (e.g., Google, Microsoft 365)
            $table->string('provider_type')->nullable()->default('Google')->after('created_by');

            // Provider-specific serial number for the panel
            $table->unsignedInteger('panel_sr_no')->nullable()->after('provider_type');

            // Enforce uniqueness within provider scope and speed lookups
            $table->index('provider_type');
            $table->unique(['provider_type', 'panel_sr_no'], 'panels_provider_type_panel_sr_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('panels', function (Blueprint $table) {
            $table->dropUnique('panels_provider_type_panel_sr_no_unique');
            $table->dropIndex(['provider_type']);
            $table->dropColumn(['provider_type', 'panel_sr_no']);
        });
    }
};
