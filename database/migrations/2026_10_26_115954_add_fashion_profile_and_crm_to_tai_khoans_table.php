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
        Schema::table('tai_khoans', function (Blueprint $table) {
            $table->string('size_thuong_mac', 20)->nullable()->after('gioi_tinh');
            $table->unsignedSmallInteger('chieu_cao')->nullable()->after('size_thuong_mac')->comment('cm');
            $table->unsignedSmallInteger('can_nang')->nullable()->after('chieu_cao')->comment('kg');
            $table->string('phong_cach', 100)->nullable()->after('can_nang');
            $table->string('nhan_khach_hang', 50)->nullable()->after('chuc_vu_id');
            $table->text('ghi_chu_admin')->nullable()->after('nhan_khach_hang');
            $table->dateTime('dang_nhap_cuoi')->nullable()->after('ngay_tao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tai_khoans', function (Blueprint $table) {
            $table->dropColumn([
                'size_thuong_mac',
                'chieu_cao',
                'can_nang',
                'phong_cach',
                'nhan_khach_hang',
                'ghi_chu_admin',
                'dang_nhap_cuoi',
            ]);
        });
    }
};
