<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('internal_order_id')->nullable()->after('id');
            
            // Add foreign key constraint to internal_orders table
            $table->foreign('internal_order_id')
                  ->references('id')
                  ->on('internal_orders')
                  ->onDelete('cascade');
            // is_internal_order_assignment
            $table->boolean('is_internal_order_assignment')->default(false);


            // Add index for better query performance
            $table->index('internal_order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['internal_order_id']);
            
            // Drop index
            $table->dropIndex(['internal_order_id']);
            
            // Drop the column
            $table->dropColumn('internal_order_id');

            $table->dropColumn('is_internal_order_assignment');
        });
    }
};
