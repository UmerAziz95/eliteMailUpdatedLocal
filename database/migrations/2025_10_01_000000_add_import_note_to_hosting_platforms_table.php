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
        Schema::table('hosting_platforms', function (Blueprint $table) {
            $table->text('import_note')->nullable()->after('tutorial_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hosting_platforms', function (Blueprint $table) {
            $table->dropColumn('import_note');
        });
    }
};