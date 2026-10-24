<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('san_phams')) {
            return;
        }

        Schema::create('san_phams', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_san_pham', 50)->unique();
            $table->string('ten_san_pham')->nullable();
            $table->decimal('gia_san_pham', 12, 0)->nullable();
            $table->decimal('gia_khuyen_mai', 12, 0)->nullable();
            $table->string('hinh_anh')->nullable();
            $table->text('mo_ta')->nullable();
            $table->unsignedInteger('luot_xem')->default(0);
            $table->unsignedInteger('da_ban')->default(0);
            $table->boolean('noi_bat')->default(false);
            $table->dateTime('ngay_nhap')->nullable();
            $table->dateTime('khuyen_mai_bat_dau')->nullable();
            $table->dateTime('khuyen_mai_ket_thuc')->nullable();
            $table->unsignedInteger('danh_muc_id')->nullable();
            $table->unsignedInteger('bo_suu_tap_id')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->index('danh_muc_id');
            $table->foreign('danh_muc_id')
                ->references('id')
                ->on('danh_mucs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('san_phams');
    }
};
