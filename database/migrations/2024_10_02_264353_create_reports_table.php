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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_type_id')->references('id')->on('report_types');
            $table->morphs('reportable');
            $table->string(column: 'content');
            // $table->boolean(column: 'status')->default(0)->comment('0: Chưa xử lý, 1: Đã xử lý');
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending')->comment("Trạng thái đơn tố cáo");
            $table->foreignId('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
