<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Configuration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pool_plans', function (Blueprint $table) {
            $table->string('provider_type')->nullable()->after('is_chargebee_synced');
        });

        // Set default provider_type for existing pool plans from Configuration
        $defaultProviderType = Configuration::get('PROVIDER_TYPE', 'Google');
        DB::table('pool_plans')->whereNull('provider_type')->update(['provider_type' => $defaultProviderType]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_plans', function (Blueprint $table) {
            $table->dropColumn('provider_type');
        });
    }
};
