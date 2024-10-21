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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')->references('id')->on('chat_rooms');
            $table->text('body');
            $table->json('is_seen')->nullable();
            $table->json('flagged')->nullable();
            $table->json('files')->nullable();
            $table->foreignId('reply_to')->nullable()->references('id')->on('messages');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
