<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('mau_sacs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_mau')->nullable();
            $table->string('ma_mau', 7)->nullable();
        });
        Schema::create('kich_thuocs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_kich_thuoc', 20)->nullable();
        });
        Schema::create('bien_thes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('san_pham_id')->nullable();
            $table->unsignedInteger('mau_sac_id')->nullable();
            $table->unsignedInteger('kich_thuoc_id')->nullable();
        });
    }

    public function test_storefront_filters_products_by_keyword_category_price_color_and_size(): void
    {
        $category = Category::create(['ten_danh_muc' => 'Áo nữ', 'trang_thai' => true]);
        $matchingProduct = Product::create([
            'ma_san_pham' => 'LINEN-01',
            'ten_san_pham' => 'Áo Linen thanh lịch',
            'gia_san_pham' => 500000,
            'gia_khuyen_mai' => 300000,
            'danh_muc_id' => $category->id,
            'trang_thai' => true,
        ]);
        $otherProduct = Product::create([
            'ma_san_pham' => 'LINEN-02',
            'ten_san_pham' => 'Áo Linen màu khác',
            'gia_san_pham' => 320000,
            'danh_muc_id' => $category->id,
            'trang_thai' => true,
        ]);
        $splitVariantProduct = Product::create([
            'ma_san_pham' => 'LINEN-03',
            'ten_san_pham' => 'Áo Linen không cùng biến thể',
            'gia_san_pham' => 320000,
            'danh_muc_id' => $category->id,
            'trang_thai' => true,
        ]);
        $colorId = DB::table('mau_sacs')->insertGetId(['ten_mau' => 'Be']);
        $otherColorId = DB::table('mau_sacs')->insertGetId(['ten_mau' => 'Đen']);
        $sizeId = DB::table('kich_thuocs')->insertGetId(['ten_kich_thuoc' => 'M']);
        $otherSizeId = DB::table('kich_thuocs')->insertGetId(['ten_kich_thuoc' => 'L']);
        DB::table('bien_thes')->insert([
            ['san_pham_id' => $matchingProduct->id, 'mau_sac_id' => $colorId, 'kich_thuoc_id' => $sizeId],
            ['san_pham_id' => $otherProduct->id, 'mau_sac_id' => $otherColorId, 'kich_thuoc_id' => $otherSizeId],
            ['san_pham_id' => $splitVariantProduct->id, 'mau_sac_id' => $colorId, 'kich_thuoc_id' => $otherSizeId],
            ['san_pham_id' => $splitVariantProduct->id, 'mau_sac_id' => $otherColorId, 'kich_thuoc_id' => $sizeId],
        ]);
        foreach ([
            ['q' => 'Linen'],
            ['category_id' => $category->id],
            ['min_price' => 250000, 'max_price' => 350000],
            ['color_id' => $colorId],
            ['size_id' => $sizeId],
            ['color_id' => $colorId, 'size_id' => $sizeId],
        ] as $index => $filters) {
            $response = $this->get(route('storefront.index', $filters));
            $response->assertOk();
            $this->assertGreaterThan(
                0,
                $response->viewData('products')->total(),
                'Filter case '.($index + 1).' should include the matching product.'
            );
        }

        $this->get(route('storefront.index', [
            'q' => 'Linen',
            'category_id' => $category->id,
            'min_price' => 250000,
            'max_price' => 350000,
            'color_id' => $colorId,
            'size_id' => $sizeId,
        ]))
            ->assertOk()
            ->assertSee('Áo Linen thanh lịch')
            ->assertDontSee('Áo Linen màu khác')
            ->assertDontSee('Áo Linen không cùng biến thể')
            ->assertSee('Be')
            ->assertSee('M');
    }

    public function test_storefront_sorts_by_price_newest_and_popularity(): void
    {
        $cheapProduct = $this->createProduct('CHEAP-01', 'Sản phẩm giá thấp', 100000, 1, '2026-01-01');
        $popularProduct = $this->createProduct('POPULAR-01', 'Sản phẩm phổ biến', 300000, 10, '2026-03-01');
        $newestProduct = $this->createProduct('NEW-01', 'Sản phẩm mới nhất', 200000, 2, '2026-06-01');
        DB::table('san_phams')->where('id', $cheapProduct->id)->update(['da_ban' => 1]);
        DB::table('san_phams')->where('id', $popularProduct->id)->update(['da_ban' => 100]);
        DB::table('san_phams')->where('id', $newestProduct->id)->update(['da_ban' => 2]);

        $this->get(route('storefront.index', ['sort' => 'price_asc']))
            ->assertOk()
            ->assertSeeInOrder(['Sản phẩm giá thấp', 'Sản phẩm mới nhất', 'Sản phẩm phổ biến']);

        $this->get(route('storefront.index', ['sort' => 'price_desc']))
            ->assertOk()
            ->assertSeeInOrder(['Sản phẩm phổ biến', 'Sản phẩm mới nhất', 'Sản phẩm giá thấp']);

        $this->get(route('storefront.index', ['sort' => 'newest']))
            ->assertOk()
            ->assertSeeInOrder(['Sản phẩm mới nhất', 'Sản phẩm phổ biến', 'Sản phẩm giá thấp']);

        $this->get(route('storefront.index', ['sort' => 'popular']))
            ->assertOk()
            ->assertSeeInOrder(['Sản phẩm phổ biến', 'Sản phẩm mới nhất', 'Sản phẩm giá thấp']);
    }

    public function test_storefront_rejects_a_price_range_with_minimum_above_maximum(): void
    {
        $this->from(route('storefront.index'))
            ->get(route('storefront.index', ['min_price' => 500000, 'max_price' => 100000]))
            ->assertRedirect(route('storefront.index'))
            ->assertSessionHasErrors('max_price');
    }

    private function createProduct(string $code, string $name, int $price, int $views, string $receivedAt): Product
    {
        $product = Product::create([
            'ma_san_pham' => $code,
            'ten_san_pham' => $name,
            'gia_san_pham' => $price,
            'ngay_nhap' => $receivedAt,
            'trang_thai' => true,
        ]);

        DB::table('san_phams')
            ->where('id', $product->id)
            ->update(['luot_xem' => $views]);

        return $product;
    }
}
