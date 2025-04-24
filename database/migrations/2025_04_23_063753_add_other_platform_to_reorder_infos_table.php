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
        Schema::table('reorder_infos', function (Blueprint $table) {

            $table->string('other_platform')->nullable()->after('hosting_platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reorder_infos', function (Blueprint $table) {
            //
            $table->dropColumn('other_platform');
        });
    }
};
