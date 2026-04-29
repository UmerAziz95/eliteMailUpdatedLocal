<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['plan_id', 'created_at'], 'orders_plan_created_at_idx');
            $table->index('user_id', 'orders_user_id_idx');
            $table->index('status_manage_by_admin', 'orders_status_manage_idx');
        });

        Schema::table('reorder_infos', function (Blueprint $table) {
            $table->index('order_id', 'reorder_infos_order_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_plan_created_at_idx');
            $table->dropIndex('orders_user_id_idx');
            $table->dropIndex('orders_status_manage_idx');
        });

        Schema::table('reorder_infos', function (Blueprint $table) {
            $table->dropIndex('reorder_infos_order_id_idx');
        });
    }
};