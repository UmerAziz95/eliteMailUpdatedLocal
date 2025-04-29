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
            //
            // bison_url
            $table->string('bison_url')->nullable()->after('other_platform');
            // bison_workspace
            $table->string('bison_workspace')->nullable()->after('bison_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reorder_infos', function (Blueprint $table) {
            //
            $table->dropColumn('bison_url');
            $table->dropColumn('bison_workspace');
        });
    }
};
