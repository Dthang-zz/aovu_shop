<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_list_shows_saved_products_and_filters(): void
    {
        $category = Category::create(['ten_danh_muc' => 'Áo nữ', 'trang_thai' => true]);
        Product::create([
            'ma_san_pham' => 'AO-LINEN-01',
            'ten_san_pham' => 'Áo sơ mi Linen',
            'gia_san_pham' => 459000,
            'danh_muc_id' => $category->id,
            'trang_thai' => true,
            'noi_bat' => true,
        ]);
        Product::create([
            'ma_san_pham' => 'DAM-MIDI-01',
            'ten_san_pham' => 'Đầm Midi',
            'gia_san_pham' => 629000,
            'trang_thai' => false,
        ]);

        $this->get(route('admin.products.index', [
            'q' => 'LINEN',
            'category_id' => $category->id,
            'status' => 'active',
            'featured' => '1',
        ]))
            ->assertOk()
            ->assertSee('Áo sơ mi Linen')
            ->assertSee('AO-LINEN-01')
            ->assertSee('Áo nữ')
            ->assertDontSee('Đầm Midi');
    }

    public function test_product_totals_update_without_sidebar_count_badges(): void
    {
        $firstProduct = Product::create([
            'ma_san_pham' => 'COUNT-001',
            'ten_san_pham' => 'Sản phẩm thứ nhất',
            'gia_san_pham' => 100000,
            'trang_thai' => true,
        ]);
        Product::create([
            'ma_san_pham' => 'COUNT-002',
            'ten_san_pham' => 'Sản phẩm thứ hai',
            'gia_san_pham' => 200000,
            'trang_thai' => true,
        ]);

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('right badge', false);

        $this->post(route('admin.products.store'), [
            'ma_san_pham' => 'COUNT-003',
            'ten_san_pham' => 'Sản phẩm thứ ba',
            'gia_san_pham' => 300000,
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('right badge', false);

        $this->put(route('admin.products.update', $firstProduct), [
            'ma_san_pham' => 'COUNT-001',
            'ten_san_pham' => 'Sản phẩm đã sửa',
            'gia_san_pham' => 110000,
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('right badge', false)
            ->assertSee('>3</h3>', false);

        $this->delete(route('admin.products.destroy', $firstProduct))
            ->assertRedirect(route('admin.products.index'));

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('right badge', false)
            ->assertSee('>2</h3>', false);
    }

    public function test_product_can_manage_multiple_images_and_product_details(): void
    {
        Storage::fake('public');
        $category = Category::create(['ten_danh_muc' => 'Áo nữ', 'trang_thai' => true]);

        $this->post(route('admin.products.store'), [
            'ma_san_pham' => 'AO-LINEN-01',
            'ten_san_pham' => 'Áo sơ mi Linen',
            'danh_muc_id' => $category->id,
            'gia_san_pham' => 459000,
            'gia_khuyen_mai' => 399000,
            'mo_ta' => 'Chất liệu linen thoáng mát.',
            'chat_lieu' => '100% linen tự nhiên.',
            'huong_dan_su_dung' => 'Giặt nhẹ, không dùng thuốc tẩy.',
            'trang_thai' => '1',
            'noi_bat' => '1',
            'hinh_anh' => [
                UploadedFile::fake()->image('linen-front.jpg'),
                UploadedFile::fake()->image('linen-back.jpg'),
            ],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('ma_san_pham', 'AO-LINEN-01')->firstOrFail();
        $images = $product->images()->orderBy('thu_tu')->get();
        $coverImage = $images[0]->link_hinh_anh;
        $removedImageId = $images[0]->id;
        $retainedImage = $images[1]->link_hinh_anh;

        $this->assertCount(2, $images);
        $this->assertSame($coverImage, $product->hinh_anh);
        Storage::disk('public')->assertExists($coverImage);
        Storage::disk('public')->assertExists($retainedImage);
        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('/storage/'.$coverImage)
            ->assertSee('/storage/'.$retainedImage)
            ->assertDontSee('http://localhost:8000/storage/'.$coverImage);
        $this->assertDatabaseHas('san_phams', [
            'id' => $product->id,
            'gia_san_pham' => 459000,
            'gia_khuyen_mai' => 399000,
            'noi_bat' => 1,
            'danh_muc_id' => $category->id,
            'chat_lieu' => '100% linen tự nhiên.',
            'huong_dan_su_dung' => 'Giặt nhẹ, không dùng thuốc tẩy.',
        ]);

        $this->put(route('admin.products.update', $product), [
            'ma_san_pham' => 'AO-LINEN-01',
            'ten_san_pham' => 'Áo Linen cập nhật',
            'danh_muc_id' => $category->id,
            'gia_san_pham' => 479000,
            'gia_khuyen_mai' => '',
            'mo_ta' => 'Mô tả mới.',
            'chat_lieu' => 'Linen pha cotton.',
            'huong_dan_su_dung' => 'Ủi ở nhiệt độ thấp.',
            'trang_thai' => '0',
            'xoa_hinh_anh_ids' => [$removedImageId],
            'hinh_anh' => [UploadedFile::fake()->image('linen-detail.png')],
        ])->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $updatedImages = $product->images()->orderBy('thu_tu')->get();
        $newImagePath = $updatedImages[1]->link_hinh_anh;

        $this->assertCount(2, $updatedImages);
        $this->assertSame($retainedImage, $product->hinh_anh);
        $this->assertDatabaseHas('san_phams', [
            'id' => $product->id,
            'ten_san_pham' => 'Áo Linen cập nhật',
            'gia_san_pham' => 479000,
            'gia_khuyen_mai' => null,
            'trang_thai' => 0,
            'hinh_anh' => $retainedImage,
            'chat_lieu' => 'Linen pha cotton.',
            'huong_dan_su_dung' => 'Ủi ở nhiệt độ thấp.',
        ]);
        Storage::disk('public')->assertMissing($coverImage);
        Storage::disk('public')->assertExists($retainedImage);
        Storage::disk('public')->assertExists($newImagePath);

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('san_phams', ['id' => $product->id]);
        Storage::disk('public')->assertMissing($retainedImage);
        Storage::disk('public')->assertMissing($newImagePath);
    }

    public function test_product_form_creates_updates_and_removes_variants(): void
    {
        $this->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Biến thể sản phẩm')
            ->assertDontSee('name="khuyen_mai_bat_dau"', false)
            ->assertDontSee('name="khuyen_mai_ket_thuc"', false);

        $this->post(route('admin.products.store'), [
            'ma_san_pham' => 'AO-CO-01',
            'ten_san_pham' => 'Áo cổ tròn',
            'gia_san_pham' => 250000,
            'trang_thai' => '1',
            'variants' => [
                [
                    'sku' => '',
                    'color' => 'Đỏ',
                    'size' => 'S',
                    'price' => '',
                    'stock' => 12,
                    'status' => ProductVariant::STATUS_ACTIVE,
                ],
                [
                    'sku' => 'AO-CO-01-DEN-M',
                    'color' => 'Đen',
                    'size' => 'M',
                    'price' => 270000,
                    'stock' => 0,
                    'status' => ProductVariant::STATUS_OUT_OF_STOCK,
                ],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('ma_san_pham', 'AO-CO-01')->firstOrFail();
        $variants = $product->variants()->orderBy('id')->get();
        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Biến thể sản phẩm')
            ->assertDontSee('name="khuyen_mai_bat_dau"', false)
            ->assertDontSee('name="khuyen_mai_ket_thuc"', false);

        $this->assertCount(2, $variants);
        $this->assertNotSame('', $variants[0]->sku);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variants[0]->id,
            'product_id' => $product->id,
            'color' => 'Đỏ',
            'size' => 'S',
            'price' => null,
            'stock' => 12,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variants[1]->id,
            'sku' => 'AO-CO-01-DEN-M',
            'price' => 270000,
            'status' => ProductVariant::STATUS_OUT_OF_STOCK,
        ]);

        $this->put(route('admin.products.update', $product), [
            'ma_san_pham' => 'AO-CO-01',
            'ten_san_pham' => 'Áo cổ tròn cập nhật',
            'gia_san_pham' => 260000,
            'trang_thai' => '1',
            'variants' => [
                [
                    'id' => $variants[0]->id,
                    'sku' => $variants[0]->sku,
                    'color' => 'Đỏ',
                    'size' => 'S',
                    'price' => 265000,
                    'stock' => 8,
                    'status' => ProductVariant::STATUS_INACTIVE,
                ],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variants[0]->id,
            'price' => 265000,
            'stock' => 8,
            'status' => ProductVariant::STATUS_INACTIVE,
        ]);
        $this->assertDatabaseMissing('product_variants', ['id' => $variants[1]->id]);

        $this->put(route('admin.products.update', $product), [
            'ma_san_pham' => 'AO-CO-01',
            'ten_san_pham' => 'Áo cổ tròn cập nhật',
            'gia_san_pham' => 260000,
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('product_variants', ['id' => $variants[0]->id]);

        $variant = $product->variants()->create([
            'sku' => 'AO-CO-01-DEN-L',
            'color' => 'Đen',
            'size' => 'L',
            'stock' => 2,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);
        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('san_phams', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_product_detail_reports_completed_order_revenue_and_orders(): void
    {
        Schema::create('bien_thes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('san_pham_id')->nullable();
        });
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang');
            $table->dateTime('ngay_dat')->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->unsignedInteger('bien_the_id')->nullable();
            $table->decimal('don_gia', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
            $table->decimal('thanh_tien', 12, 0)->nullable();
        });

        $product = Product::create([
            'ma_san_pham' => 'SALE-001',
            'ten_san_pham' => 'Sản phẩm có doanh thu',
            'gia_san_pham' => 100000,
            'trang_thai' => true,
        ]);
        $variantId = DB::table('bien_thes')->insertGetId(['san_pham_id' => $product->id]);
        $completedOrderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'ORD-COMPLETED',
            'ngay_dat' => '2026-10-20 10:00:00',
            'trang_thai' => Order::STATUS_COMPLETED,
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            'don_hang_id' => $completedOrderId,
            'bien_the_id' => $variantId,
            'don_gia' => 125000,
            'so_luong' => 2,
            'thanh_tien' => 250000,
        ]);

        $pendingOrderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'ORD-PENDING',
            'ngay_dat' => '2026-10-21 10:00:00',
            'trang_thai' => Order::STATUS_PENDING,
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            'don_hang_id' => $pendingOrderId,
            'bien_the_id' => $variantId,
            'don_gia' => 125000,
            'so_luong' => 1,
            'thanh_tien' => 125000,
        ]);

        $this->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertSee('250.000 ₫')
            ->assertSee('ORD-COMPLETED')
            ->assertDontSee('ORD-PENDING');
    }

    public function test_product_form_rejects_variant_sku_already_used_by_another_product(): void
    {
        $existingProduct = Product::create([
            'ma_san_pham' => 'EXISTING-01',
            'ten_san_pham' => 'Sản phẩm hiện có',
            'gia_san_pham' => 100000,
            'trang_thai' => true,
        ]);
        $existingProduct->variants()->create([
            'sku' => 'SHARED-VARIANT-SKU',
            'color' => 'Đen',
            'size' => 'M',
            'stock' => 2,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);

        $this->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'ma_san_pham' => 'NEW-001',
                'ten_san_pham' => 'Sản phẩm mới',
                'gia_san_pham' => 200000,
                'trang_thai' => '1',
                'variants' => [
                    [
                        'sku' => 'shared-variant-sku',
                        'color' => 'Đỏ',
                        'size' => 'S',
                        'stock' => 1,
                        'status' => ProductVariant::STATUS_ACTIVE,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('variants.0.sku');

        $this->assertDatabaseCount('san_phams', 1);
        $this->assertDatabaseCount('product_variants', 1);
    }

    public function test_product_validation_rejects_duplicate_sku_invalid_prices_and_category(): void
    {
        Product::create([
            'ma_san_pham' => 'SKU-EXISTS',
            'ten_san_pham' => 'Sản phẩm có sẵn',
            'gia_san_pham' => 100000,
            'trang_thai' => true,
        ]);

        $this->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'ma_san_pham' => 'SKU-EXISTS',
                'ten_san_pham' => '',
                'danh_muc_id' => 9999,
                'gia_san_pham' => 100000,
                'gia_khuyen_mai' => 100000,
                'trang_thai' => '1',
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors([
                'ma_san_pham',
                'ten_san_pham',
                'danh_muc_id',
                'gia_khuyen_mai',
            ]);

        $this->assertDatabaseCount('san_phams', 1);
    }

    public function test_product_cannot_be_deleted_while_variants_reference_it(): void
    {
        $product = Product::create([
            'ma_san_pham' => 'AO-HAS-VARIANTS',
            'ten_san_pham' => 'Áo có biến thể',
            'gia_san_pham' => 250000,
            'trang_thai' => true,
        ]);

        Schema::create('bien_thes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('san_pham_id')->nullable();
        });
        DB::table('bien_thes')->insert(['san_pham_id' => $product->id]);

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('san_phams', ['id' => $product->id]);
    }
}
