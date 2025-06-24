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
        $table->longText('nested_menu')->nullable()->after('sub_menu');
    });
}

public function down(): void
{
    Schema::table('sidebar_navigations', function (Blueprint $table) {
        $table->dropColumn('nested_menu');
    });
}

};
