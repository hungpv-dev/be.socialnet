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
        Schema::create('user_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->references('id')->on('stories');
            $table->string('emoji')->nullable();
            $table->foreignId('user_id')->references('id')->on('users');
            $table->boolean('seen')->default(false)->comment('1: Đã xem, 0: Chưa xem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stories');
    }
};
