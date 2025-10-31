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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('customer_access', ['normal', 'trial', 'full'])
                  ->default('normal')
                  ->after('type')
                  ->comment('Customer access level: normal for regular customers, trial for first-time customers, full for all customers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('customer_access');
        });
    }
};
