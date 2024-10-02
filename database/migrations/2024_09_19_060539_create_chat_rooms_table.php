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
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_type_id');
            $table->foreign('chat_type_id')->references('id')->on('chat_types');
            $table->json('name');
            $table->json('user')->nullable();
            $table->json('admin')->nullable();
            $table->json('last_remove')->nullable();
            $table->json('last_active');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};
