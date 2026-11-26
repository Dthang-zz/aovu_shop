<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chuc_vus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_chuc_vu')->nullable();
            $table->text('mo_ta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chuc_vus');
    }
};
