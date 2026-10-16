<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('danh_mucs')) {
            return;
        }

        Schema::create('danh_mucs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_danh_muc');
            $table->unsignedInteger('danh_muc_cha_id')->nullable();
            $table->text('mo_ta')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->index('danh_muc_cha_id');
            $table->foreign('danh_muc_cha_id')
                ->references('id')
                ->on('danh_mucs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danh_mucs');
    }
};
