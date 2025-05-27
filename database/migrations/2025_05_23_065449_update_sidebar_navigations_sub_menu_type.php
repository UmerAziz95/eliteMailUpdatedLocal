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
        Schema::table('sidebar_navigations', function (Blueprint $table) {
           $table->json('sub_menu')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidebar_navigations', function (Blueprint $table) {
            // Revert it to previous type (assuming it was text; change if different)
            $table->text('sub_menu')->change();
        });
    }
};
