<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('yeu_cau_doi_tras')) {
            return;
        }

        Schema::create('yeu_cau_doi_tras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable()->index();
            $table->unsignedInteger('chi_tiet_don_hang_id')->nullable()->index();
            $table->unsignedInteger('tai_khoan_id')->nullable()->index();
            $table->unsignedTinyInteger('loai_yeu_cau')->nullable();
            $table->unsignedInteger('so_luong')->nullable();
            $table->text('ly_do')->nullable();
            $table->string('hinh_anh')->nullable();
            $table->text('phan_hoi')->nullable();
            $table->dateTime('ngay_tao')->nullable()->index();
            $table->unsignedTinyInteger('trang_thai')->default(0)->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yeu_cau_doi_tras');
    }
};
