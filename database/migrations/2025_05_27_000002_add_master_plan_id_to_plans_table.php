<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMasterPlanIdToPlansTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedBigInteger('master_plan_id')->nullable()->after('id');
            
            $table->foreign('master_plan_id')->references('id')->on('master_plans')->onDelete('cascade');
            $table->index('master_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropForeign(['master_plan_id']);
            $table->dropIndex(['master_plan_id']);
            $table->dropColumn('master_plan_id');        });
    }
}
