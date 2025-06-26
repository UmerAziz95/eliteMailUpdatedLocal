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
        Schema::table('reorder_infos', function (Blueprint $table) {
            // initial_total_inboxes
            $table->integer('initial_total_inboxes')->default(0)->after('total_inboxes')->comment('Initial total inboxes at the time of reorder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reorder_infos', function (Blueprint $table) {
            $table->dropColumn('initial_total_inboxes');
        });
    }
};
