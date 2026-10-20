<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('don_hangs')) {
            Schema::create('don_hangs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('ma_don_hang', 50)->unique();
                $table->unsignedInteger('tai_khoan_id')->nullable()->index();
                $table->string('ten_nguoi_nhan', 100)->nullable();
                $table->string('email_nguoi_nhan')->nullable();
                $table->string('sdt_nguoi_nhan', 15)->nullable();
                $table->string('dia_chi_nguoi_nhan')->nullable();
                $table->dateTime('ngay_dat')->nullable()->index();
                $table->decimal('tien_hang', 12, 0)->nullable();
                $table->decimal('phi_van_chuyen', 12, 0)->nullable();
                $table->unsignedInteger('voucher_id')->nullable();
                $table->decimal('tien_giam', 12, 0)->nullable();
                $table->decimal('tong_tien', 12, 0)->nullable();
                $table->text('ghi_chu')->nullable();
                $table->unsignedInteger('phuong_thuc_thanh_toan_id')->nullable();
                $table->unsignedTinyInteger('trang_thai_thanh_toan')->nullable();
                $table->unsignedTinyInteger('trang_thai')->default(0)->index();
            });
        }

        if (! Schema::hasTable('chi_tiet_don_hangs')) {
            Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('don_hang_id')->nullable()->index();
                $table->unsignedInteger('bien_the_id')->nullable();
                $table->string('ten_san_pham')->nullable();
                $table->decimal('don_gia', 12, 0)->nullable();
                $table->unsignedInteger('so_luong')->nullable();
                $table->decimal('thanh_tien', 12, 0)->nullable();
            });
        }

        if (! Schema::hasTable('lich_su_trang_thai_don_hangs')) {
            Schema::create('lich_su_trang_thai_don_hangs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('don_hang_id');
                $table->unsignedTinyInteger('trang_thai');
                $table->string('ghi_chu')->nullable();
                $table->unsignedInteger('nguoi_cap_nhat_id')->nullable();
                $table->dateTime('ngay_cap_nhat')->nullable();
                $table->index(['don_hang_id', 'ngay_cap_nhat']);
            });
        }
    }

    public function down(): void
    {
        // These tables are shared with the imported legacy schema, so rollback must not drop order data.
    }
};
