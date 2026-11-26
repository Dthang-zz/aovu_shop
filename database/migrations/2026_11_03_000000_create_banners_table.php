<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('image_path');
                $table->string('link_url', 2048)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        } else {
            Schema::table('banners', function (Blueprint $table) {
                if (! Schema::hasColumn('banners', 'title')) {
                    $table->string('title')->nullable();
                }

                if (! Schema::hasColumn('banners', 'image_path')) {
                    $table->string('image_path')->nullable();
                }

                if (! Schema::hasColumn('banners', 'link_url')) {
                    $table->string('link_url', 2048)->nullable();
                }

                if (! Schema::hasColumn('banners', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0);
                }

                if (! Schema::hasColumn('banners', 'is_active')) {
                    $table->boolean('is_active')->default(false);
                }
            });

            DB::table('banners')->whereNull('title')->update([
                'title' => DB::raw('ten_banner'),
            ]);
            DB::table('banners')->whereNull('image_path')->update([
                'image_path' => DB::raw('hinh_anh'),
            ]);
            DB::table('banners')->whereNull('link_url')->update([
                'link_url' => DB::raw('lien_ket'),
            ]);
            DB::table('banners')->update([
                'sort_order' => DB::raw('thu_tu'),
                'is_active' => DB::raw('trang_thai'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
