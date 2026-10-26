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
        Schema::create('promotions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_chuong_trinh', 255);
            $table->decimal('phan_tram_giam', 5, 2);
            $table->dateTime('ngay_bat_dau');
            $table->dateTime('ngay_ket_thuc');
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
        });

        Schema::create('promotion_products', function (Blueprint $table) {
            $table->unsignedInteger('promotion_id');
            $table->integer('product_id');
            $table->primary(['promotion_id', 'product_id']);
            $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('san_phams')->cascadeOnDelete();
        });

        if (Schema::hasTable('don_hangs')) {
            Schema::table('don_hangs', function (Blueprint $table) {
                if (! Schema::hasColumn('don_hangs', 'tien_giam_khuyen_mai')) {
                    $table->decimal('tien_giam_khuyen_mai', 12, 0)->default(0);
                }

                if (! Schema::hasColumn('don_hangs', 'tien_giam_voucher')) {
                    $table->decimal('tien_giam_voucher', 12, 0)->default(0);
                }
            });
        }

        if (Schema::hasTable('chi_tiet_don_hangs')) {
            Schema::table('chi_tiet_don_hangs', function (Blueprint $table) {
                if (! Schema::hasColumn('chi_tiet_don_hangs', 'gia_goc')) {
                    $table->decimal('gia_goc', 12, 0)->nullable();
                }

                if (! Schema::hasColumn('chi_tiet_don_hangs', 'tien_giam_khuyen_mai')) {
                    $table->decimal('tien_giam_khuyen_mai', 12, 0)->default(0);
                }

                if (! Schema::hasColumn('chi_tiet_don_hangs', 'khuyen_mai_id')) {
                    $table->unsignedInteger('khuyen_mai_id')->nullable();
                }

                if (! Schema::hasColumn('chi_tiet_don_hangs', 'ten_khuyen_mai')) {
                    $table->string('ten_khuyen_mai')->nullable();
                }

                if (! Schema::hasColumn('chi_tiet_don_hangs', 'phan_tram_giam')) {
                    $table->decimal('phan_tram_giam', 5, 2)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep the order price snapshots after rollback to preserve historical transaction data.
        Schema::dropIfExists('promotion_products');
        Schema::dropIfExists('promotions');
    }
};
