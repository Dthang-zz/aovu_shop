<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::query()->firstOrCreate(
            ['ten_banner' => 'Banner mẫu'],
            [
                'hinh_anh' => null,
                'lien_ket' => null,
                'thu_tu' => 0,
                'trang_thai' => false,
            ]
        );
    }
}
