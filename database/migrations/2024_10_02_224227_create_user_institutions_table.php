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
        Schema::create('user_institutions', function (Blueprint $table) {
            $table->id();
            $table->morphs('instituteable');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('status')->default(false)->comment('1: Đang, 0: Đã');
            $table->string('major')->nullable();
            $table->foreignId('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_institutions');
    }
};
