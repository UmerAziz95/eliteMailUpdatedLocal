<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChargebeeCustomerIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'chargebee_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('chargebee_customer_id')->nullable()->after('phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'chargebee_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('chargebee_customer_id');
            });
        }
    }
};
