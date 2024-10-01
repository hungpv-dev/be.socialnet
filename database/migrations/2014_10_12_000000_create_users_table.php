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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name',100);
            $table->string('email')->unique();
            $table->string('phone',20)->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover_avatar')->nullable();
            $table->string('authentication')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('time_offline')->default(now());
            $table->string('password');
            $table->boolean('is_online')->default(false)->comment('1: Online, 0: Offline');
            $table->boolean('is_active')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
