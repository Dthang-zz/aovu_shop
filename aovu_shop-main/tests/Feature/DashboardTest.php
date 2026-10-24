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
            $table->dateTime('ngay_tao')->nullable();
        });
    }

    public function test_dashboard_displays_live_metrics_orders_sales_and_low_stock(): void
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
            'ngay_tao' => $today->copy()->startOfMonth()->addDays(2),
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
            ->assertSee('DASH-001-WH-M')
            ->assertSee('Còn 4');
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
}
