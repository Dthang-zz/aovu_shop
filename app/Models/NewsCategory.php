<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bai_viets', function (Blueprint $table) {
            $table->id();
            $table->string('tieu_de', 200);
            $table->string('slug')->unique();
            $table->string('mo_ta_ngan', 500)->nullable();
            $table->longText('noi_dung');
            $table->string('anh_dai_dien')->nullable();
            $table->foreignId('danh_muc_bai_viet_id')->nullable()
                ->constrained('danh_muc_bai_viets')->nullOnDelete();
            $table->string('tac_gia', 120)->nullable();
            $table->string('tu_khoa_seo', 255)->nullable();
            $table->string('mo_ta_seo', 320)->nullable();
            $table->enum('trang_thai', ['draft', 'published', 'hidden'])->default('draft');
            $table->unsignedBigInteger('luot_xem')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['trang_thai', 'published_at']);
            $table->index('danh_muc_bai_viet_id');
        });
    }
    public function down(): void 
    {
        Schema::dropIfExists('bai_viets');
    }
};