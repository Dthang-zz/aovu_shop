<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang');
            $table->unsignedInteger('tai_khoan_id')->nullable();
            $table->string('ten_nguoi_nhan')->nullable();
            $table->dateTime('ngay_dat')->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
            $table->unsignedTinyInteger('trang_thai_thanh_toan')->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->unsignedInteger('bien_the_id')->nullable();
            $table->decimal('don_gia', 12, 0)->nullable();
            $table->decimal('thanh_tien', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
        });
        Schema::create('bien_thes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('san_pham_id')->nullable();
            $table->string('sku');
            $table->decimal('gia_bien_the', 12, 0)->nullable();
            $table->integer('so_luong_ton')->nullable();
        });
        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ho_ten')->nullable();
            $table->string('email')->nullable();
            $table->dateTime('ngay_tao')->nullable();
        });
    }

    public function test_dashboard_displays_live_metrics_orders_and_sales_without_stock_alerts(): void
    {
        $today = now()->startOfDay();
        $productId = DB::table('san_phams')->insertGetId([
            'ma_san_pham' => 'DASH-001',
            'ten_san_pham' => 'Áo Linen',
            'gia_san_pham' => 250000,
            'gia_khuyen_mai' => 200000,
        ]);
        $variantId = DB::table('bien_thes')->insertGetId([
            'san_pham_id' => $productId,
            'sku' => 'DASH-001-WH-M',
            'gia_bien_the' => 200000,
            'so_luong_ton' => 4,
        ]);
        $todayOrderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'ORD-TODAY',
            'ten_nguoi_nhan' => 'Khách hôm nay',
            'ngay_dat' => $today->copy()->addHours(10),
            'tong_tien' => 400000,
            'trang_thai_thanh_toan' => 1,
            'trang_thai' => 0,
        ]);
        $yesterdayOrderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'ORD-YESTERDAY',
            'ten_nguoi_nhan' => 'Khách hôm qua',
            'ngay_dat' => $today->copy()->subDay()->addHours(10),
            'tong_tien' => 150000,
            'trang_thai_thanh_toan' => 1,
            'trang_thai' => 3,
        ]);
        DB::table('don_hangs')->insert([
            'ma_don_hang' => 'ORD-UNPAID',
            'ngay_dat' => $today->copy()->addHours(11),
            'tong_tien' => 900000,
            'trang_thai_thanh_toan' => 0,
            'trang_thai' => 4,
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            ['don_hang_id' => $todayOrderId, 'bien_the_id' => $variantId, 'so_luong' => 2],
            ['don_hang_id' => $yesterdayOrderId, 'bien_the_id' => $variantId, 'so_luong' => 3],
        ]);
        DB::table('tai_khoans')->insert([
            'ho_ten' => 'Khách mới',
            'email' => 'new@example.test',
            'ngay_tao' => $today->copy()->subDay(),
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('todayRevenue', 400000.0)
            ->assertViewHas('ordersToday', 2)
            ->assertViewHas('pendingOrders', 1)
            ->assertViewHas('newCustomers', 1)
            ->assertViewHas('revenueTotal', 550000.0)
            ->assertSee('₫ 400.000')
            ->assertSee('ORD-TODAY')
            ->assertSee('ORD-YESTERDAY')
            ->assertSee('Áo Linen')
            ->assertSee('5 đã bán')
            ->assertDontSee('Cảnh báo tồn kho')
            ->assertDontSee('DASH-001-WH-M')
            ->assertSee('Còn 4');
    }

    public function test_dashboard_shows_period_sales_repeat_buyers_category_and_growth_metrics(): void
    {
        $today = now()->startOfDay();
        $categoryId = DB::table('danh_mucs')->insertGetId([
            'ten_danh_muc' => 'Áo nữ',
        ]);
        $productId = DB::table('san_phams')->insertGetId([
            'ma_san_pham' => 'DASH-002',
            'ten_san_pham' => 'Áo bán chạy',
            'gia_san_pham' => 200000,
            'danh_muc_id' => $categoryId,
            'trang_thai' => true,
        ]);
        $unsoldProductId = DB::table('san_phams')->insertGetId([
            'ma_san_pham' => 'DASH-003',
            'ten_san_pham' => 'Áo chưa bán',
            'gia_san_pham' => 150000,
            'trang_thai' => true,
        ]);
        $variantId = DB::table('bien_thes')->insertGetId([
            'san_pham_id' => $productId,
            'sku' => 'DASH-002-WH-M',
            'gia_bien_the' => 175000,
            'so_luong_ton' => 20,
        ]);
        DB::table('bien_thes')->insert([
            'san_pham_id' => $unsoldProductId,
            'sku' => 'DASH-003-WH-M',
            'gia_bien_the' => 150000,
            'so_luong_ton' => 30,
        ]);
        DB::table('tai_khoans')->insert([
            ['id' => 1, 'ho_ten' => 'Khách mới kỳ này', 'email' => 'new@example.test', 'ngay_tao' => $today->copy()->subDay()],
            ['id' => 2, 'ho_ten' => 'Khách quay lại', 'email' => 'returning@example.test', 'ngay_tao' => $today->copy()->subMonths(2)],
        ]);

        $previousOrderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'ORD-PREVIOUS-PERIOD',
            'tai_khoan_id' => 2,
            'ngay_dat' => $today->copy()->subDays(7)->addHours(10),
            'tong_tien' => 250000,
            'trang_thai_thanh_toan' => 1,
            'trang_thai' => 3,
        ]);
        $currentOrderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'ORD-CURRENT-PAID',
            'tai_khoan_id' => 2,
            'ngay_dat' => $today->copy()->subDays(2)->addHours(10),
            'tong_tien' => 350000,
            'trang_thai_thanh_toan' => 1,
            'trang_thai' => 3,
        ]);
        DB::table('don_hangs')->insert([
            'ma_don_hang' => 'ORD-CURRENT-PENDING',
            'tai_khoan_id' => 1,
            'ten_nguoi_nhan' => 'Khách mới kỳ này',
            'ngay_dat' => $today->copy()->subDay()->addHours(10),
            'tong_tien' => 100000,
            'trang_thai_thanh_toan' => 0,
            'trang_thai' => 0,
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            ['don_hang_id' => $previousOrderId, 'bien_the_id' => $variantId, 'don_gia' => 125000, 'thanh_tien' => 250000, 'so_luong' => 2],
            ['don_hang_id' => $currentOrderId, 'bien_the_id' => $variantId, 'don_gia' => 175000, 'thanh_tien' => 350000, 'so_luong' => 2],
        ]);
        DB::table('yeu_cau_doi_tras')->insert([
            [
                'don_hang_id' => $currentOrderId,
                'tai_khoan_id' => 2,
                'loai_yeu_cau' => 1,
                'ngay_tao' => $today->copy()->subDay(),
                'trang_thai' => 0,
            ],
            [
                'don_hang_id' => $currentOrderId,
                'tai_khoan_id' => 2,
                'loai_yeu_cau' => 2,
                'ngay_tao' => $today->copy(),
                'trang_thai' => 4,
            ],
        ]);

        $this->get(route('admin.dashboard', ['period' => '7d']))
            ->assertOk()
            ->assertViewHas('ordersInPeriod', 2)
            ->assertViewHas('customersInPeriod', 2)
            ->assertViewHas('productsSold', 2)
            ->assertViewHas('productCount', 2)
            ->assertViewHas('newCustomers', 1)
            ->assertViewHas('pendingOrders', 1)
            ->assertViewHas('revenueTotal', 350000.0)
            ->assertViewHas('statusCounts', fn (array $counts): bool => $counts[0]['count'] === 1 && $counts[3]['count'] === 1)
            ->assertViewHas('topSellers', fn ($products): bool => (int) $products->first()->quantity_sold === 2)
            ->assertViewHas('slowProducts', fn ($products): bool => (int) $products->first()->quantity_sold === 0)
            ->assertViewHas('categoryRevenue', fn ($categories): bool => (float) $categories->first()->revenue === 350000.0)
            ->assertViewHas('returnStats', fn (array $stats): bool => $stats['total'] === 2 && $stats['pending'] === 1 && $stats['exchanges'] === 1 && $stats['returns'] === 1)
            ->assertSee('Sản phẩm bán chậm')
            ->assertSee('Đơn chờ xác nhận')
            ->assertDontSee('Khách hàng quay lại')
            ->assertDontSee('Tăng trưởng so với kỳ trước');
    }

    public function test_dashboard_period_filters_chart_and_invalid_period_uses_default(): void
    {
        $today = now()->startOfDay();
        DB::table('don_hangs')->insert([
            'ma_don_hang' => 'ORD-PAID',
            'ngay_dat' => $today->copy()->subDays(8),
            'tong_tien' => 123000,
            'trang_thai_thanh_toan' => 1,
            'trang_thai' => 3,
        ]);

        $this->get(route('admin.dashboard', ['period' => '7d']))
            ->assertOk()
            ->assertViewHas('revenueTotal', 0.0);

        $this->get(route('admin.dashboard', ['period' => '30d']))
            ->assertOk()
            ->assertViewHas('revenueTotal', 123000.0)
            ->assertViewHas('chartValues', fn (array $values) => count($values) === 30 && array_sum($values) === 123000.0);

        $this->get(route('admin.dashboard', ['period' => '12m']))
            ->assertOk()
            ->assertViewHas('revenueTotal', 123000.0)
            ->assertViewHas('chartLabels', fn (array $labels) => count($labels) === 12);

        $this->get(route('admin.dashboard', ['period' => 'invalid']))
            ->assertOk()
            ->assertSee('7 ngày gần nhất');
    }

    public function test_dashboard_custom_date_range_filters_all_period_metrics_inclusively(): void
    {
        $today = now()->startOfDay();
        $start = $today->copy()->subDays(3);
        $end = $today->copy()->subDays(2);
        DB::table('don_hangs')->insert([
            [
                'ma_don_hang' => 'ORD-CUSTOM-START',
                'ngay_dat' => $start,
                'tong_tien' => 125000,
                'trang_thai_thanh_toan' => 1,
                'trang_thai' => 3,
            ],
            [
                'ma_don_hang' => 'ORD-CUSTOM-END',
                'ngay_dat' => $end->copy()->endOfDay(),
                'tong_tien' => 175000,
                'trang_thai_thanh_toan' => 1,
                'trang_thai' => 3,
            ],
            [
                'ma_don_hang' => 'ORD-OUTSIDE-RANGE',
                'ngay_dat' => $start->copy()->subSecond(),
                'tong_tien' => 900000,
                'trang_thai_thanh_toan' => 1,
                'trang_thai' => 3,
            ],
        ]);

        $this->get(route('admin.dashboard', [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ]))
            ->assertOk()
            ->assertViewHas('period', 'custom')
            ->assertViewHas('startDate', $start->toDateString())
            ->assertViewHas('endDate', $end->toDateString())
            ->assertViewHas('ordersInPeriod', 2)
            ->assertViewHas('revenueTotal', 300000.0)
            ->assertViewHas('chartValues', fn (array $values): bool => count($values) === 2 && array_sum($values) === 300000.0)
            ->assertSee('name="start_date"', false)
            ->assertSee('name="end_date"', false)
            ->assertSee('Áp dụng khoảng ngày')
            ->assertSee('ORD-CUSTOM-START')
            ->assertSee('ORD-CUSTOM-END')
            ->assertDontSee('ORD-OUTSIDE-RANGE');
    }

    public function test_dashboard_custom_date_range_rejects_end_before_start(): void
    {
        $this->get(route('admin.dashboard', [
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-19',
        ]))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors([
                'end_date' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
            ]);
    }

    public function test_dashboard_custom_date_range_requires_both_dates(): void
    {
        $this->get(route('admin.dashboard', ['start_date' => '2026-10-20']))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors('end_date');
    }
}
