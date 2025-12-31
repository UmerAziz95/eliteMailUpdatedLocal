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
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->string('provider_type')->nullable()->after('status_manage_by_admin');
        });

        // Set default value from Configuration table for existing records
        $defaultProviderType = Configuration::get('PROVIDER_TYPE', 'Google');
        DB::table('pool_orders')
            ->whereNull('provider_type')
            ->update(['provider_type' => $defaultProviderType]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->dropColumn('provider_type');
        });
    }
};
