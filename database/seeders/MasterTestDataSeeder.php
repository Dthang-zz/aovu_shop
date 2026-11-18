<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo danh mục mẫu (nếu bảng danh_mucs tồn tại)
        $category = DB::table('danh_mucs')->insertGetId([
            'ten_danh_muc' => 'Thời trang Nam/Nữ',
            'trang_thai' => 1,
        ]);

        // 2. Tạo sản phẩm mẫu trong bảng san_phams
        DB::table('san_phams')->insert([
            'ma_san_pham' => 'SP001',
            'ten_san_pham' => 'Áo sơ mi Linen cao cấp',
            'gia_san_pham' => 250000,
            'so_luong_ton' => 80,
            'danh_muc_id' => $category,
            'trang_thai' => 1,
        ]);

        // Tồn kho được quản lý trực tiếp trên sản phẩm.
    }
}
