<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('vouchers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_voucher')->unique();
            $table->string('mo_ta')->nullable();
            $table->unsignedTinyInteger('loai_giam')->nullable();
            $table->decimal('gia_tri_giam', 12, 0)->nullable();
            $table->decimal('giam_toi_da', 12, 0)->nullable();
            $table->decimal('don_toi_thieu', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
            $table->unsignedInteger('da_su_dung')->nullable();
            $table->dateTime('ngay_bat_dau')->nullable();
            $table->dateTime('ngay_ket_thuc')->nullable();
            $table->boolean('trang_thai')->nullable();
        });
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang')->unique();
            $table->unsignedInteger('tai_khoan_id')->nullable();
            $table->string('ten_nguoi_nhan')->nullable();
            $table->string('email_nguoi_nhan')->nullable();
            $table->string('sdt_nguoi_nhan')->nullable();
            $table->string('dia_chi_nguoi_nhan')->nullable();
            $table->dateTime('ngay_dat')->nullable();
            $table->decimal('tien_hang', 12, 0)->nullable();
            $table->decimal('phi_van_chuyen', 12, 0)->nullable();
            $table->unsignedInteger('voucher_id')->nullable();
            $table->decimal('tien_giam', 12, 0)->nullable();
            $table->decimal('tien_giam_khuyen_mai', 12, 0)->nullable();
            $table->decimal('tien_giam_voucher', 12, 0)->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->unsignedInteger('phuong_thuc_thanh_toan_id')->nullable();
            $table->unsignedTinyInteger('trang_thai_thanh_toan')->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->unsignedInteger('bien_the_id')->nullable();
            $table->string('ten_san_pham')->nullable();
            $table->decimal('don_gia', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
            $table->decimal('thanh_tien', 12, 0)->nullable();
            $table->decimal('gia_goc', 12, 0)->nullable();
            $table->decimal('tien_giam_khuyen_mai', 12, 0)->nullable();
            $table->unsignedInteger('khuyen_mai_id')->nullable();
            $table->string('ten_khuyen_mai')->nullable();
            $table->decimal('phan_tram_giam', 5, 2)->nullable();
        });
    }

    public function test_customer_sees_active_promotion_and_prices(): void
    {
        $product = $this->createProduct('STORE-001');
        $this->createPromotion($product, 20);

        $this->get(route('shop.products.show', $product))
            ->assertOk()
            ->assertSee('Áo STORE-001')
            ->assertSee('500.000')
            ->assertSee('400.000')
            ->assertSee('20%');
    }

    public function test_customer_checkout_recalculates_campaign_and_voucher_and_stores_price_snapshots(): void
    {
        $product = $this->createProduct('STORE-002');
        $variant = $product->variants()->create([
            'sku' => 'STORE-002-BL-M',
            'color' => 'Xanh',
            'size' => 'M',
            'price' => 500000,
            'stock' => 5,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);
        $promotionId = $this->createPromotion($product, 20);
        DB::table('vouchers')->insert([
            'ma_voucher' => 'SAVE10',
            'loai_giam' => 1,
            'gia_tri_giam' => 10,
            'giam_toi_da' => 20000,
            'don_toi_thieu' => 100000,
            'so_luong' => 10,
            'da_su_dung' => 0,
            'ngay_bat_dau' => now()->subDay(),
            'ngay_ket_thuc' => now()->addDay(),
            'trang_thai' => true,
        ]);

        $this->withSession(['storefront_cart' => [
            $variant->id => ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2],
        ]])->post(route('shop.checkout.store'), [
            'ten_nguoi_nhan' => 'Nguyễn An',
            'email_nguoi_nhan' => 'an@example.test',
            'sdt_nguoi_nhan' => '0912345678',
            'dia_chi_nguoi_nhan' => '12 đường Mẫu',
            'voucher_code' => 'SAVE10',
        ])->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionMissing('storefront_cart');

        $order = DB::table('don_hangs')->first();
        $this->assertDatabaseHas('don_hangs', [
            'id' => $order->id,
            'tien_hang' => 1000000,
            'tien_giam_khuyen_mai' => 200000,
            'tien_giam_voucher' => 20000,
            'tien_giam' => 220000,
            'tong_tien' => 780000,
        ]);
        $this->assertDatabaseHas('chi_tiet_don_hangs', [
            'don_hang_id' => $order->id,
            'bien_the_id' => null,
            'ten_san_pham' => 'Áo STORE-002 (Xanh / M)',
            'don_gia' => 400000,
            'gia_goc' => 500000,
            'so_luong' => 2,
            'thanh_tien' => 800000,
            'tien_giam_khuyen_mai' => 200000,
            'khuyen_mai_id' => $promotionId,
            'ten_khuyen_mai' => 'Giảm giá STORE-002',
            'phan_tram_giam' => 20,
        ]);
        $this->assertDatabaseHas('vouchers', ['ma_voucher' => 'SAVE10', 'da_su_dung' => 1]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 3]);
    }

    public function test_product_price_ignores_disabled_upcoming_and_other_product_promotions(): void
    {
        $product = $this->createProduct('STORE-004');
        $otherProduct = $this->createProduct('STORE-005');
        $this->createPromotion($product, 30, ['trang_thai' => false]);
        $this->createPromotion($product, 40, [
            'ngay_bat_dau' => now()->addDay(),
            'ngay_ket_thuc' => now()->addDays(2),
        ]);
        $this->createPromotion($otherProduct, 50);

        $this->get(route('shop.products.show', $product))
            ->assertOk()
            ->assertSee('500.000')
            ->assertDontSee('30%')
            ->assertDontSee('40%')
            ->assertDontSee('50%');
    }

    public function test_customer_cannot_use_voucher_below_minimum_purchase(): void
    {
        $product = $this->createProduct('STORE-003');
        DB::table('vouchers')->insert([
            'ma_voucher' => 'MINIMUM',
            'loai_giam' => 2,
            'gia_tri_giam' => 10000,
            'don_toi_thieu' => 600000,
            'so_luong' => 10,
            'da_su_dung' => 0,
            'ngay_bat_dau' => now()->subDay(),
            'ngay_ket_thuc' => now()->addDay(),
            'trang_thai' => true,
        ]);

        $this->withSession(['storefront_cart' => [
            'product-'.$product->id => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1],
        ]])->post(route('shop.checkout.store'), [
            'ten_nguoi_nhan' => 'Nguyễn An',
            'email_nguoi_nhan' => 'an@example.test',
            'sdt_nguoi_nhan' => '0912345678',
            'dia_chi_nguoi_nhan' => '12 đường Mẫu',
            'voucher_code' => 'MINIMUM',
        ])->assertRedirect()
            ->assertSessionHasErrors('voucher_code');

        $this->assertDatabaseCount('don_hangs', 0);
    }

    public function test_customer_cannot_place_order_with_a_voucher_that_is_not_active(): void
    {
        $product = $this->createProduct('STORE-006');
        DB::table('vouchers')->insert([
            'ma_voucher' => 'INACTIVE',
            'loai_giam' => 2,
            'gia_tri_giam' => 10000,
            'so_luong' => 10,
            'da_su_dung' => 0,
            'ngay_bat_dau' => now()->subDay(),
            'ngay_ket_thuc' => now()->addDay(),
            'trang_thai' => false,
        ]);

        $this->withSession(['storefront_cart' => [
            'product-'.$product->id => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1],
        ]])->post(route('shop.checkout.store'), [
            'ten_nguoi_nhan' => 'Nguyễn An',
            'email_nguoi_nhan' => 'an@example.test',
            'sdt_nguoi_nhan' => '0912345678',
            'dia_chi_nguoi_nhan' => '12 đường Mẫu',
            'voucher_code' => 'INACTIVE',
        ])->assertRedirect()
            ->assertSessionHasErrors('voucher_code');

        $this->assertDatabaseCount('don_hangs', 0);
    }

    public function test_customer_cannot_place_order_when_variant_stock_is_insufficient(): void
    {
        $product = $this->createProduct('STORE-007');
        $variant = $product->variants()->create([
            'sku' => 'STORE-007-BL-M',
            'color' => 'Xanh',
            'size' => 'M',
            'price' => 500000,
            'stock' => 1,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);

        $this->withSession(['storefront_cart' => [
            $variant->id => ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2],
        ]])->post(route('shop.checkout.store'), [
            'ten_nguoi_nhan' => 'Nguyễn An',
            'email_nguoi_nhan' => 'an@example.test',
            'sdt_nguoi_nhan' => '0912345678',
            'dia_chi_nguoi_nhan' => '12 đường Mẫu',
        ])->assertRedirect()
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('don_hangs', 0);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 1]);
    }

    public function test_customer_cannot_checkout_a_cart_over_the_order_amount_limit(): void
    {
        $product = $this->createProduct('STORE-OVER-LIMIT');
        $product->update(['gia_san_pham' => 900000000000]);

        $this->withSession(['storefront_cart' => [
            'product-'.$product->id => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2],
        ]])->post(route('shop.checkout.store'), [
            'ten_nguoi_nhan' => 'Nguyễn An',
            'email_nguoi_nhan' => 'an@example.test',
            'sdt_nguoi_nhan' => '0912345678',
            'dia_chi_nguoi_nhan' => '12 đường Mẫu',
        ])->assertRedirect()
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('don_hangs', 0);
    }

    private function createProduct(string $code): Product
    {
        return Product::create([
            'ma_san_pham' => $code,
            'ten_san_pham' => 'Áo '.$code,
            'gia_san_pham' => 500000,
            'trang_thai' => true,
        ]);
    }

    private function createPromotion(Product $product, int $discount, array $overrides = []): int
    {
        $promotionId = DB::table('promotions')->insertGetId([
            'ten_chuong_trinh' => 'Giảm giá '.$product->ma_san_pham,
            'phan_tram_giam' => $discount,
            'ngay_bat_dau' => now()->subDay(),
            'ngay_ket_thuc' => now()->addDay(),
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);
        DB::table('promotion_products')->insert([
            'promotion_id' => $promotionId,
            'product_id' => $product->id,
        ]);

        return $promotionId;
    }
}
