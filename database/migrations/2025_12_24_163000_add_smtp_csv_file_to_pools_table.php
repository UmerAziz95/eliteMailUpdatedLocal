<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pools', function (Blueprint $table) {
            $table->longText('smtp_csv_file')->nullable()->after('smtp_accounts_data');
            $table->string('smtp_csv_filename')->nullable()->after('smtp_csv_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pools', function (Blueprint $table) {
            $table->dropColumn(['smtp_csv_file', 'smtp_csv_filename']);
        });
    }
};
