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
        Schema::table('order_emails', function (Blueprint $table) {
            $table->unsignedBigInteger('order_split_id')->nullable()->after('order_id');
            $table->unsignedBigInteger('contractor_id')->nullable()->after('user_id');
            
            // Add foreign key constraints
            $table->foreign('order_split_id')->references('id')->on('order_panel_split')->onDelete('cascade');
            $table->foreign('contractor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_emails', function (Blueprint $table) {
            $table->dropForeign(['order_split_id']);
            $table->dropForeign(['contractor_id']);
            $table->dropColumn(['order_split_id', 'contractor_id']);
        });
    }
};
