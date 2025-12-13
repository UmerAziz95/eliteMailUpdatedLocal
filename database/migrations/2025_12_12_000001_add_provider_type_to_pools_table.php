<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Configuration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('pools', 'provider_type')) {
            Schema::table('pools', function (Blueprint $table) {
                $table->string('provider_type')->nullable()->after('sending_platform');
            });
            
            // Set default provider_type for existing pools from Configuration
            $defaultProviderType = Configuration::get('PROVIDER_TYPE', 'Google');
            DB::table('pools')->whereNull('provider_type')->update(['provider_type' => $defaultProviderType]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pools', function (Blueprint $table) {
            $table->dropColumn('provider_type');
        });
    }
};
