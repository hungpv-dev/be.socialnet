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
        Schema::create('profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('address')->nullable();
            $table->string('hometown')->nullable();
            $table->string('gender');
            $table->date('birthday');
            $table->string('relationship')->nullable();
            $table->unsignedBigInteger('follower')->default(0);
            $table->unsignedBigInteger('friend_counts')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
