<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'subscription_id')) {
            Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_id')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'subscription_status')) {
            Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_status')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'plan_id')) {
            Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subscription_id', 'subscription_status']);
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
};