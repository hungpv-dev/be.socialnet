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
            $table->unsignedBigInteger(column: 'chat_room_id');
            $table->text(column: 'body');
            $table->json('is_seen')->nullable();
            $table->json('flagged')->nullable();
            $table->json('files')->nullable();
            $table->unsignedBigInteger('reply_to')->nullable();
            $table->unsignedBigInteger(column: 'user_id');
            $table->timestamps();
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
