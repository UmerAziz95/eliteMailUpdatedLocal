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
        Schema::table('user_order_panel_assignment', function (Blueprint $table) {
            $table->enum('status', ['active', 'reassigned'])->default('active')->after('contractor_id');
            $table->unsignedBigInteger('original_order_panel_id')->nullable()->after('status');
            $table->timestamp('reassigned_at')->nullable()->after('original_order_panel_id');
            $table->text('reassignment_note')->nullable()->after('reassigned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_order_panel_assignment', function (Blueprint $table) {
            $table->dropColumn(['status', 'original_order_panel_id', 'reassigned_at', 'reassignment_note']);
        });
    }
};
