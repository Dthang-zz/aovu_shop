<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vi_hoan_tiens', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tai_khoan_id');
            $table->decimal('so_du', 15, 0)->default(0);
            $table->tinyInteger('trang_thai')->default(1)->comment('1=Hoạt động, 0=Đã khóa');
            $table->datetime('ngay_tao')->nullable();
            $table->datetime('ngay_cap_nhat')->nullable();

            $table->unique('tai_khoan_id');
        });

        Schema::create('giao_dich_vis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vi_id');
            $table->tinyInteger('loai_giao_dich')->comment('1=Hoàn tiền vào ví, 2=Sử dụng từ ví, 3=Điều chỉnh thủ công');
            $table->decimal('so_tien', 15, 0);
            $table->decimal('so_du_truoc', 15, 0)->default(0);
            $table->decimal('so_du_sau', 15, 0)->default(0);
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->unsignedInteger('yeu_cau_doi_tra_id')->nullable();
            $table->string('ghi_chu', 500)->nullable();
            $table->unsignedInteger('nguoi_thuc_hien_id')->nullable()->comment('Admin thực hiện');
            $table->datetime('ngay_tao')->nullable();

            $table->foreign('vi_id')->references('id')->on('vi_hoan_tiens');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giao_dich_vis');
        Schema::dropIfExists('vi_hoan_tiens');
    }
};
