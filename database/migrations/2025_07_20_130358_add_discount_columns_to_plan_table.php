<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('tier_discount_value', 8, 2)->nullable()->after('price'); // or after the appropriate column
            $table->enum('tier_discount_type', ['percentage', 'fixed'])->nullable()->after('tier_discount_value');
            $table->decimal('actual_price_before_discount', 10, 2)->nullable()->after('tier_discount_type');
            $table->boolean('is_discounted')->nullable()->after('actual_price_before_discount');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'tier_discount_value',
                'tier_discount_type',
                'actual_price_before_discount',
            ]);
        });
    }
};
