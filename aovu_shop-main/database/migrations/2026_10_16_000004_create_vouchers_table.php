<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vouchers')) {
            return;
        }

        Schema::create('vouchers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_voucher', 50)->unique();
            $table->string('mo_ta', 255)->nullable();
            $table->unsignedTinyInteger('loai_giam')->nullable()->comment('1: Phần trăm, 2: Số tiền cố định');
            $table->decimal('gia_tri_giam', 12, 0)->nullable();
            $table->decimal('giam_toi_da', 12, 0)->nullable();
            $table->decimal('don_toi_thieu', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
            $table->unsignedInteger('da_su_dung')->default(0);
            $table->dateTime('ngay_bat_dau')->nullable()->index();
            $table->dateTime('ngay_ket_thuc')->nullable()->index();
            $table->unsignedTinyInteger('trang_thai')->default(1)->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
