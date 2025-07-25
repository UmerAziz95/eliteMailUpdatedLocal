<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('master_plans', function (Blueprint $table) {
            $table->boolean('is_discounted')->default(false)->after('description'); // adjust 'plan_type' to the column after which you want to place this
        });
    }

    public function down(): void
    {
        Schema::table('master_plans', function (Blueprint $table) {
            $table->dropColumn('is_discounted');
        });
    }
};
