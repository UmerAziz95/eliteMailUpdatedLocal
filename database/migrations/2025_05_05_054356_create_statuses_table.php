<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();    // Status name (e.g., pending)
            $table->string('badge');             // Badge class (e.g., warning, success)
            $table->timestamps();                // created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};

