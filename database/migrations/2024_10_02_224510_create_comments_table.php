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
        Schema::create('comments', function (Blueprint $table) {
            // $table->id();
            // $table->foreignId('user_id')->references('id')->on('users');
            // $table->foreignId('post_id')->references('id')->on('posts');
            // $table->json(column: 'content');
            // $table->foreignId('parent_id')->references('id')->on('comments')->default(null);
            // $table->timestamps();
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Liên kết với bảng users, xóa người dùng sẽ xóa comment
            $table->foreignId('post_id')->constrained()->onDelete('cascade'); // Liên kết với bảng posts, xóa bài viết sẽ xóa comment
            $table->json('content'); // Đổi thành text cho nội dung comment
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade'); // Cho phép null, tự liên kết
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
