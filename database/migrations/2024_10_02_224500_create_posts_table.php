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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users');
            $table->text(column: 'content')->comment('Nội dung bài post');
            $table->json(column: 'data')->comment('Ảnh và video bài viết');
            $table->foreignId('share_id')->references('id')->on('posts')->nullable();
            $table->enum('status', ['public', 'friend', 'private'])->default('friend');
            $table->boolean(column: 'is_active')->default(true)->comment('1: Hiển thị, 0: Bị khóa');
            $table->enum('type', ['avatar','background','post'])->default('post');
            $table->integer(column: 'emoji_count')->default(0);
            $table->integer(column: 'comment_count')->default(0);
            $table->integer(column: 'share_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
