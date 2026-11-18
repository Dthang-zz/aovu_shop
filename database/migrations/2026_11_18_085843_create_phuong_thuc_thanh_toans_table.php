<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phuong_thuc_thanh_toans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_phuong_thuc', 50)->unique();
            $table->string('ten_phuong_thuc', 100);
            $table->string('mo_ta', 255)->nullable();
            $table->boolean('trang_thai')->default(true)->index();
            $table->timestamps();
        });

        DB::table('phuong_thuc_thanh_toans')->insert([
            [
                'ma_phuong_thuc' => 'COD',
                'ten_phuong_thuc' => 'Thanh toán khi nhận hàng (COD)',
                'mo_ta' => 'Thanh toán tiền mặt khi nhận hàng.',
                'trang_thai' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ma_phuong_thuc' => 'BANK_TRANSFER',
                'ten_phuong_thuc' => 'Chuyển khoản ngân hàng',
                'mo_ta' => 'Chuyển khoản ngân hàng theo hướng dẫn của cửa hàng.',
                'trang_thai' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('phuong_thuc_thanh_toans');
    }
};
