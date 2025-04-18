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
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('min_inbox')->default(1)->after('duration');
            $table->integer('max_inbox')->default(5)->after('min_inbox');
            $table->dropColumn('features');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->json('features')->nullable()->after('duration');
            $table->dropColumn(['min_inbox', 'max_inbox']);
        });
    }
};
