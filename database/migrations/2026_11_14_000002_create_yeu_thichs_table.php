<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('yeu_thichs')) {
            return;
        }

        Schema::create('yeu_thichs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tai_khoan_id')->nullable()->index();
            $table->unsignedInteger('san_pham_id')->nullable()->index();
            $table->dateTime('ngay_them')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yeu_thichs');
    }
};
