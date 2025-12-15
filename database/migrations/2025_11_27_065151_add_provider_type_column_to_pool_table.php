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
        Schema::table('pools', function (Blueprint $table) {
            $table->enum('provider_type', ['Google', 'Microsoft 365'])->default('Google')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('pools', 'provider_type')) {
            Schema::table('pools', function (Blueprint $table) {
                $table->dropColumn('provider_type');
            });
        }
    }
};
