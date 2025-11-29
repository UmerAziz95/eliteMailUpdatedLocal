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
        if (!Schema::hasColumn('configurations', 'last_change')) {
            Schema::table('configurations', function (Blueprint $table) {
                $table->json('last_change')->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('configurations', 'last_change')) {
            Schema::table('configurations', function (Blueprint $table) {
                $table->dropColumn('last_change');
            });
        }
    }
};
