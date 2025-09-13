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
    Schema::create('tickets_imap_emails', function (Blueprint $table) {
    $table->id();
    $table->string('message_id')->unique(); // prevent duplicates
    $table->string('folder'); // inbox, sent, etc.
    $table->string('subject')->nullable();
    $table->text('from')->nullable();
    $table->text('to')->nullable();
    $table->text('cc')->nullable();
    $table->text('bcc')->nullable();
    $table->longText('body')->nullable();
    $table->timestamp('date')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
