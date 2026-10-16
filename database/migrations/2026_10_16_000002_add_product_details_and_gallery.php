<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('san_phams', function (Blueprint $table) {
            if (!Schema::hasColumn('san_phams', 'chat_lieu')) {
                $table->text('chat_lieu')->nullable();
            }

            if (!Schema::hasColumn('san_phams', 'huong_dan_su_dung')) {
                $table->text('huong_dan_su_dung')->nullable();
            }
        });

        if (!Schema::hasTable('hinh_anh_san_phams')) {
            Schema::create('hinh_anh_san_phams', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('san_pham_id');
                $table->string('link_hinh_anh');
                $table->unsignedInteger('thu_tu')->default(0);
                $table->index('san_pham_id');
                $table->foreign('san_pham_id')
                    ->references('id')
                    ->on('san_phams')
                    ->cascadeOnDelete();
            });
        }

        $legacyImages = DB::table('san_phams')
            ->whereNotNull('hinh_anh')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('hinh_anh_san_phams')
                    ->whereColumn('hinh_anh_san_phams.san_pham_id', 'san_phams.id')
                    ->whereColumn('hinh_anh_san_phams.link_hinh_anh', 'san_phams.hinh_anh');
            })
            ->get(['id', 'hinh_anh']);

        foreach ($legacyImages as $image) {
            DB::table('hinh_anh_san_phams')->insert([
                'san_pham_id' => $image->id,
                'link_hinh_anh' => $image->hinh_anh,
                'thu_tu' => 0,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hinh_anh_san_phams');

        Schema::table('san_phams', function (Blueprint $table) {
            if (Schema::hasColumn('san_phams', 'chat_lieu')) {
                $table->dropColumn('chat_lieu');
            }

            if (Schema::hasColumn('san_phams', 'huong_dan_su_dung')) {
                $table->dropColumn('huong_dan_su_dung');
            }
        });
    }
};
