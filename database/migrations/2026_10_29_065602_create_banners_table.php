<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('ten_banner');
            $table->string('hinh_anh')->nullable();
            $table->text('lien_ket')->nullable();
            $table->unsignedInteger('thu_tu')->default(0);
            $table->boolean('trang_thai')->default(false);
            $table->index(['trang_thai', 'thu_tu']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
