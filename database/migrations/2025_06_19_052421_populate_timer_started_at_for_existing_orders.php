<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing orders where status is 'pending' and timer_started_at is null
        // Set timer_started_at to created_at for these orders
        DB::table('orders')
            ->where('status_manage_by_admin', 'pending')
            ->whereNull('timer_started_at')
            ->update(['timer_started_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset timer_started_at to null for all orders
        DB::table('orders')->update(['timer_started_at' => null]);
    }
};
