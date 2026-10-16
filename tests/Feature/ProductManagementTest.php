<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
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

    public function test_sidebar_product_count_updates_after_create_update_and_delete(): void
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
            ->assertSee('>2</span>', false);

        $this->post(route('admin.products.store'), [
            'ma_san_pham' => 'COUNT-003',
            'ten_san_pham' => 'Sản phẩm thứ ba',
            'gia_san_pham' => 300000,
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('>3</span>', false);

        $this->put(route('admin.products.update', $firstProduct), [
            'ma_san_pham' => 'COUNT-001',
            'ten_san_pham' => 'Sản phẩm đã sửa',
            'gia_san_pham' => 110000,
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('>3</span>', false)
            ->assertSee('>3</h3>', false);

        $this->delete(route('admin.products.destroy', $firstProduct))
            ->assertRedirect(route('admin.products.index'));

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('>2</span>', false)
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
