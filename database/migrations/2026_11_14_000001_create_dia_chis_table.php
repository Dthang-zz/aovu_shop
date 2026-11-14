<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dia_chis')) {
            return;
        }

        Schema::create('dia_chis', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tai_khoan_id')->nullable()->index();
            $table->string('ten_nguoi_nhan', 100)->nullable();
            $table->string('sdt_nguoi_nhan', 15)->nullable();
            $table->string('tinh_thanh', 100)->nullable();
            $table->string('quan_huyen', 100)->nullable();
            $table->string('phuong_xa', 100)->nullable();
            $table->string('dia_chi_chi_tiet', 255)->nullable();
            $table->boolean('mac_dinh')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dia_chis');
    }
};
