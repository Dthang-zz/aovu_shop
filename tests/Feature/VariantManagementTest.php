<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_variant_list_filters_by_search_product_color_size_and_status(): void
    {
        $product = $this->createProduct('TEE-001', 'Áo thun Linen');
        $otherProduct = $this->createProduct('DRESS-001', 'Đầm Midi');
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEE-WHITE-M',
            'color' => 'Trắng',
            'size' => 'M',
            'price' => 200000,
            'stock' => 5,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEE-BLACK-L',
            'color' => 'Đen',
            'size' => 'L',
            'price' => null,
            'stock' => 0,
            'status' => ProductVariant::STATUS_OUT_OF_STOCK,
        ]);
        ProductVariant::create([
            'product_id' => $otherProduct->id,
            'sku' => 'DRESS-WHITE-M',
            'color' => 'Trắng',
            'size' => 'M',
            'price' => null,
            'stock' => 4,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);

        $this->get(route('admin.variants.index', [
            'q' => 'TEE',
            'product_id' => $product->id,
            'color' => 'Trắng',
            'size' => 'M',
            'status' => ProductVariant::STATUS_ACTIVE,
        ]))
            ->assertOk()
            ->assertSee('TEE-WHITE-M')
            ->assertDontSee('TEE-BLACK-L')
            ->assertDontSee('DRESS-WHITE-M');
    }

    public function test_variant_can_be_created_updated_and_deleted_with_all_business_statuses(): void
    {
        $product = $this->createProduct('SHIRT-001', 'Áo sơ mi');
        $this->get(route('admin.variants.create'))
            ->assertOk()
            ->assertSee('Hết hàng');

        $this->post(route('admin.variants.store'), [
            'product_id' => $product->id,
            'sku' => 'shirt-black-m',
            'color' => 'Đen',
            'size' => 'M',
            'price' => 350000,
            'stock' => 0,
            'status' => ProductVariant::STATUS_OUT_OF_STOCK,
        ])->assertRedirect(route('admin.variants.index'));

        $variant = ProductVariant::where('sku', 'SHIRT-BLACK-M')->firstOrFail();
        $this->get(route('admin.variants.edit', $variant))
            ->assertOk()
            ->assertSee('Tạm ngừng bán');

        $this->get(route('admin.variants.show', $variant))
            ->assertOk()
            ->assertSee('SHIRT-BLACK-M')
            ->assertSee('Áo sơ mi')
            ->assertSee('Đen')
            ->assertSee('M')
            ->assertSee('350.000 ₫')
            ->assertSee('Hết hàng')
            ->assertSee('Chỉnh sửa biến thể');

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'product_id' => $product->id,
            'price' => 350000,
            'stock' => 0,
            'status' => ProductVariant::STATUS_OUT_OF_STOCK,
        ]);

        $this->put(route('admin.variants.update', $variant), [
            'product_id' => $product->id,
            'sku' => 'shirt-black-m',
            'color' => 'Đen',
            'size' => 'M',
            'price' => '',
            'stock' => 6,
            'status' => ProductVariant::STATUS_ACTIVE,
        ])->assertRedirect(route('admin.variants.index'));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'SHIRT-BLACK-M',
            'price' => null,
            'stock' => 6,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);

        $this->delete(route('admin.variants.destroy', $variant))
            ->assertRedirect(route('admin.variants.index'));

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_variant_rejects_invalid_status_and_negative_stock(): void
    {
        $product = $this->createProduct('PANTS-001', 'Quần dài');

        $this->from(route('admin.variants.create'))
            ->post(route('admin.variants.store'), [
                'product_id' => $product->id,
                'sku' => 'PANTS-001-BLACK-M',
                'color' => 'Đen',
                'size' => 'M',
                'stock' => -1,
                'status' => 'paused',
            ])
            ->assertRedirect(route('admin.variants.create'))
            ->assertSessionHasErrors(['stock', 'status']);

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_variant_detail_returns_not_found_for_missing_variant(): void
    {
        $this->get(route('admin.variants.show', 999999))
            ->assertNotFound();
    }

    private function createProduct(string $sku, string $name): Product
    {
        return Product::create([
            'ma_san_pham' => $sku,
            'ten_san_pham' => $name,
            'gia_san_pham' => 300000,
            'trang_thai' => true,
        ]);
    }
}
