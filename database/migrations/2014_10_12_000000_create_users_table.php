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
            $table->string('authentication')->nullable()->comment('1: Có bật xác thực 2 yếu tố, 2: Không bật');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('time_offline')->default(now())->comment('Thời gian offline');
            $table->string('password');
            $table->boolean('is_online')->default(false)->comment('1: Online, 0: Offline');
            $table->boolean('is_active')->default(false)->comment('0: Hoạt động, 1: Bị khóa');
            $table->string('address')->nullable()->comment('Địa chỉ');
            $table->string('hometown')->nullable()->comment('Quê quán');
            $table->string('gender')->nullable()->comment('Giới tính');
            $table->date('birthday')->nullable()->comment('Ngày sinh');
            $table->enum('relationship', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->unsignedBigInteger('follower')->default(0);
            $table->unsignedBigInteger('friend_counts')->default(0);
            $table->timestamps();
            $table->softDeletes();
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
