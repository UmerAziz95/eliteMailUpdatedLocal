<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesForDomainOptimization extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pools', function (Blueprint $table) {
            // Add composite index for better query performance
            $table->index(['status_manage_by_admin', 'inboxes_per_domain'], 'pools_status_inboxes_idx');
        });
        
        Schema::table('pool_orders', function (Blueprint $table) {
            // Add index for user domain queries
            $table->index(['user_id', 'id'], 'pool_orders_user_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pools', function (Blueprint $table) {
            $table->dropIndex('pools_status_inboxes_idx');
        });
        
        Schema::table('pool_orders', function (Blueprint $table) {
            $table->dropIndex('pool_orders_user_id_idx');
        });
    }
}