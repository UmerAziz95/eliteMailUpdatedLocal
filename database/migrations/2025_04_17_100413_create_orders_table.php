<?php
// database/migrations/xxxx_xx_xx_create_orders_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('chargebee_invoice_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status')->default('pending');
            // $table->text('reason')->nullable();
            $table->string('currency')->default('USD');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        // Set auto-increment starting value
        DB::statement("ALTER TABLE orders AUTO_INCREMENT = 1000;");
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
