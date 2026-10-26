<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromotionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_percentage_campaign_for_selected_products(): void
    {
        $product = Product::create([
            'ma_san_pham' => 'PROMO-001',
            'ten_san_pham' => 'Áo khuyến mãi',
            'gia_san_pham' => 500000,
            'trang_thai' => true,
        ]);

        $this->post(route('admin.promotions.store'), [
            'ten_chuong_trinh' => 'Sale tháng 10',
            'phan_tram_giam' => 20,
            'san_pham_ids' => [$product->id],
            'ngay_bat_dau' => now()->addDay()->format('Y-m-d\TH:i'),
            'ngay_ket_thuc' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.promotions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('promotions', [
            'ten_chuong_trinh' => 'Sale tháng 10',
            'phan_tram_giam' => 20,
            'trang_thai' => 1,
        ]);
        $this->assertDatabaseHas('promotion_products', [
            'product_id' => $product->id,
        ]);
    }

    public function test_admin_cannot_create_campaign_with_invalid_discount_dates_or_product(): void
    {
        $product = Product::create([
            'ma_san_pham' => 'PROMO-002',
            'ten_san_pham' => 'Sản phẩm hợp lệ',
            'gia_san_pham' => 150000,
            'trang_thai' => true,
        ]);

        $this->from(route('admin.promotions.create'))
            ->post(route('admin.promotions.store'), [
                'ten_chuong_trinh' => 'Khuyến mãi sai',
                'phan_tram_giam' => 101,
                'san_pham_ids' => [$product->id, 999999],
                'ngay_bat_dau' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'ngay_ket_thuc' => now()->addDay()->format('Y-m-d\TH:i'),
                'trang_thai' => '1',
            ])
            ->assertRedirect(route('admin.promotions.create'))
            ->assertSessionHasErrors([
                'phan_tram_giam',
                'san_pham_ids.1',
                'ngay_ket_thuc',
            ]);

        $this->assertDatabaseCount('promotions', 0);
        $this->assertDatabaseCount('promotion_products', 0);
    }

    public function test_admin_can_list_campaigns_with_status_and_selected_products(): void
    {
        $product = Product::create([
            'ma_san_pham' => 'PROMO-003',
            'ten_san_pham' => 'Sản phẩm đang giảm',
            'gia_san_pham' => 250000,
            'trang_thai' => true,
        ]);
        $promotionId = DB::table('promotions')->insertGetId([
            'ten_chuong_trinh' => 'Sale cuối tuần',
            'phan_tram_giam' => 15,
            'ngay_bat_dau' => now()->subHour(),
            'ngay_ket_thuc' => now()->addHour(),
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('promotion_products')->insert([
            'promotion_id' => $promotionId,
            'product_id' => $product->id,
        ]);

        $this->get(route('admin.promotions.index'))
            ->assertOk()
            ->assertSee('Sale cuối tuần')
            ->assertSee('Sản phẩm đang giảm')
            ->assertSee('15%');
    }

    public function test_admin_can_view_campaign_details_and_applied_products(): void
    {
        $product = Product::create([
            'ma_san_pham' => 'PROMO-DETAIL-001',
            'ten_san_pham' => 'Áo áp dụng khuyến mãi',
            'gia_san_pham' => 350000,
            'trang_thai' => true,
        ]);
        $promotionId = DB::table('promotions')->insertGetId([
            'ten_chuong_trinh' => 'Chi tiết Sale',
            'phan_tram_giam' => 25,
            'ngay_bat_dau' => '2026-10-26 09:00:00',
            'ngay_ket_thuc' => '2026-10-30 23:00:00',
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('promotion_products')->insert([
            'promotion_id' => $promotionId,
            'product_id' => $product->id,
        ]);

        $this->get(route('admin.promotions.show', $promotionId))
            ->assertOk()
            ->assertSee('Chi tiết Sale')
            ->assertSee('25%')
            ->assertSee('26/10/2026 09:00')
            ->assertSee('30/10/2026 23:00')
            ->assertSee('Đang bật')
            ->assertSee('Áo áp dụng khuyến mãi')
            ->assertSee('PROMO-DETAIL-001')
            ->assertSee('350.000');
    }
}
