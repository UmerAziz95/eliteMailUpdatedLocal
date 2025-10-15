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
        Schema::table('pool_panel_splits', function (Blueprint $table) {
            $table->integer('assigned_space')->default(0)->after('domains')->comment('Track the space that has been assigned from this split');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_panel_splits', function (Blueprint $table) {
            $table->dropColumn('assigned_space');
        });
    }
};
