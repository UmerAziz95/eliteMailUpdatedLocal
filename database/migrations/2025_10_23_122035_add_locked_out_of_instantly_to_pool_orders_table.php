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
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->boolean('locked_out_of_instantly')->default(0)->after('cancelled_at');
            $table->timestamp('locked_out_at')->nullable()->after('locked_out_of_instantly');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->dropColumn(['locked_out_of_instantly', 'locked_out_at']);
        });
    }
};
