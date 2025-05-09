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
            $table->string('billing_company')->nullable();
            $table->string('billing_address2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_zip')->nullable();
            $table->string('billing_landmark')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $this->dropColumn('billing_company');
            $this->dropColumn('billing_address2');
            $this->dropColumn('billing_city');
            $this->dropColumn('billing_state');
            $this->dropColumn('billing_country');
            $this->dropColumn('billing_zip');
            $this->dropColumn('billing_landmark');
        });

    }
};
