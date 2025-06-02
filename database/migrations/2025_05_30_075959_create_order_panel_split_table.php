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
        Schema::create('order_panel_split', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('panel_id')->nullable();
            $table->unsignedBigInteger('order_panel_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();

            $table->json('domains')->nullable(); // e.g., list of assigned domains/areas to this panel split

            $table->timestamps();
        });
    }

      /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_panel_split');
    }
};
