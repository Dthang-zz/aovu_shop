<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Category; // Hoặc đường dẫn model Category của nhóm bạn
use App\Models\Product;
use App\Models\ProductVariant;

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
        $productId = DB::table('san_phams')->insertGetId([
            'ma_san_pham' => 'SP001',
            'ten_san_pham' => 'Áo sơ mi Linen cao cấp',
            'gia_san_pham' => 250000,
            'danh_muc_id' => $category,
            'trang_thai' => 1,
        ]);

        // 3. Tạo biến thể mẫu cho sản phẩm trên
        ProductVariant::create([
            'product_id' => $productId,
            'sku' => 'SP001-TRANG-M',
            'color' => 'Trắng',
            'size' => 'M',
            'price' => 250000,
            'stock' => 50,
            'status' => 'active'
        ]);

        ProductVariant::create([
            'product_id' => $productId,
            'sku' => 'SP001-DEN-L',
            'color' => 'Đen',
            'size' => 'L',
            'price' => 260000,
            'stock' => 30,
            'status' => 'active'
        ]);
    }
}
