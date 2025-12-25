<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('smtp_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name (e.g., "SendGrid", "Mailgun")
            $table->string('url')->nullable(); // Provider URL
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add smtp_provider_id to pools table
        Schema::table('pools', function (Blueprint $table) {
            $table->unsignedBigInteger('smtp_provider_id')->nullable()->after('smtp_provider_url');
            $table->foreign('smtp_provider_id')->references('id')->on('smtp_providers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pools', function (Blueprint $table) {
            $table->dropForeign(['smtp_provider_id']);
            $table->dropColumn('smtp_provider_id');
        });

        Schema::dropIfExists('smtp_providers');
    }
};
