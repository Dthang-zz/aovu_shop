<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-product automatic promotions are replaced by cart discount codes (legacy `vouchers` table).
     * Order lines keep their saved promotion snapshot columns for history.
     */
    public function up(): void
    {
        Schema::dropIfExists('promotion_products');
        Schema::dropIfExists('promotions');
    }

    /**
     * Recreate the empty tables; dropped campaigns are not restored.
     */
    public function down(): void
    {
        if (! Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('ten_chuong_trinh', 255);
                $table->string('loai_giam', 20)->default('percent');
                $table->decimal('phan_tram_giam', 5, 2)->nullable();
                $table->decimal('so_tien_giam', 12, 0)->nullable();
                $table->decimal('giam_toi_da', 12, 0)->nullable();
                $table->decimal('gia_toi_thieu', 12, 0)->nullable();
                $table->dateTime('ngay_bat_dau');
                $table->dateTime('ngay_ket_thuc');
                $table->boolean('trang_thai')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('promotion_products')) {
            Schema::create('promotion_products', function (Blueprint $table): void {
                $table->unsignedInteger('promotion_id');
                $table->integer('product_id');
                $table->primary(['promotion_id', 'product_id']);
                $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
                $table->foreign('product_id')->references('id')->on('san_phams')->cascadeOnDelete();
            });
        }
    }
};
