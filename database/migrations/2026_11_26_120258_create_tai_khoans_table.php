<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->increments('id');

            $table->string('ho_ten', 50)->nullable();
            $table->string('anh_dai_dien')->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('so_dien_thoai', 15)->nullable();
            $table->tinyInteger('gioi_tinh')->nullable();
            $table->string('mat_khau')->nullable();
            $table->unsignedInteger('chuc_vu_id')->nullable();
            $table->tinyInteger('trang_thai')->nullable();
            $table->dateTime('ngay_tao')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tai_khoans');
    }
};
