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
        Schema::table('order_panel_split', function (Blueprint $table) {
            $table->string('uploaded_file_path', 500)->nullable()->after('domains');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_panel_split', function (Blueprint $table) {
            $table->dropColumn('uploaded_file_path');
        });
    }
};
