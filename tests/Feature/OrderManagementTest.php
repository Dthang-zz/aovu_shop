<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ho_ten')->nullable();
        });
        Schema::create('phuong_thuc_thanh_toans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_phuong_thuc')->nullable();
        });
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang');
            $table->unsignedInteger('tai_khoan_id')->nullable();
            $table->string('ten_nguoi_nhan')->nullable();
            $table->string('email_nguoi_nhan')->nullable();
            $table->string('sdt_nguoi_nhan')->nullable();
            $table->string('dia_chi_nguoi_nhan')->nullable();
            $table->dateTime('ngay_dat')->nullable();
            $table->decimal('tien_hang', 12, 0)->nullable();
            $table->decimal('phi_van_chuyen', 12, 0)->nullable();
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
        Schema::create('lich_su_trang_thai_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
            $table->string('ghi_chu')->nullable();
            $table->unsignedInteger('nguoi_cap_nhat_id')->nullable();
            $table->dateTime('ngay_cap_nhat')->nullable();
        });
    }

    public function test_order_list_shows_orders_and_filters_by_search_status_and_payment(): void
    {
        $this->createOrder([
            'ma_don_hang' => 'ORD-100',
            'ten_nguoi_nhan' => 'Khách hàng thử nghiệm',
            'trang_thai' => Order::STATUS_PENDING,
            'trang_thai_thanh_toan' => Order::PAYMENT_UNPAID,
        ]);
        $this->createOrder([
            'ma_don_hang' => 'ORD-200',
            'ten_nguoi_nhan' => 'Khách hàng khác',
            'trang_thai' => Order::STATUS_SHIPPING,
            'trang_thai_thanh_toan' => Order::PAYMENT_PAID,
        ]);

        $this->get(route('admin.orders.index', [
            'q' => 'ORD-100',
            'status' => (string) Order::STATUS_PENDING,
            'payment_status' => (string) Order::PAYMENT_UNPAID,
        ]))
            ->assertOk()
            ->assertSee('ORD-100')
            ->assertSee('Khách hàng thử nghiệm')
            ->assertSee('Chờ xác nhận')
            ->assertDontSee('ORD-200');
    }

    public function test_order_detail_shows_items_payment_and_status_history(): void
    {
        $orderId = $this->createOrder([
            'ma_don_hang' => 'ORD-DETAIL',
            'phuong_thuc_thanh_toan_id' => 1,
            'trang_thai_thanh_toan' => Order::PAYMENT_PAID,
        ]);
        DB::table('phuong_thuc_thanh_toans')->insert([
            'id' => 1,
            'ten_phuong_thuc' => 'Thanh toán khi nhận hàng',
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            'don_hang_id' => $orderId,
            'ten_san_pham' => 'Áo Linen',
            'don_gia' => 250000,
            'so_luong' => 2,
            'thanh_tien' => 500000,
        ]);
        DB::table('lich_su_trang_thai_don_hangs')->insert([
            'don_hang_id' => $orderId,
            'trang_thai' => Order::STATUS_PENDING,
            'ghi_chu' => 'Đơn mới được tạo.',
            'ngay_cap_nhat' => now(),
        ]);

        $this->get(route('admin.orders.show', $orderId))
            ->assertOk()
            ->assertSee('ORD-DETAIL')
            ->assertSee('Áo Linen')
            ->assertSee('500.000 ₫')
            ->assertSee('Thanh toán khi nhận hàng')
            ->assertSee('Đã thanh toán')
            ->assertSee('Đơn mới được tạo.');
    }

    public function test_order_detail_shows_saved_promotion_and_voucher_discount_breakdown(): void
    {
        $orderId = $this->createOrder([
            'ma_don_hang' => 'ORD-PROMO',
            'tien_hang' => 500000,
            'tien_giam' => 125000,
            'tien_giam_khuyen_mai' => 100000,
            'tien_giam_voucher' => 25000,
            'tong_tien' => 375000,
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            'don_hang_id' => $orderId,
            'ten_san_pham' => 'Áo giảm giá',
            'don_gia' => 400000,
            'gia_goc' => 500000,
            'so_luong' => 1,
            'thanh_tien' => 400000,
            'tien_giam_khuyen_mai' => 100000,
            'khuyen_mai_id' => 2,
            'ten_khuyen_mai' => 'Sale mùa lễ',
            'phan_tram_giam' => 20,
        ]);

        $this->get(route('admin.orders.show', $orderId))
            ->assertOk()
            ->assertSee('Sale mùa lễ')
            ->assertSee('500.000 ₫')
            ->assertSee('Giảm từ chương trình')
            ->assertSee('100.000 ₫')
            ->assertSee('Giảm từ voucher')
            ->assertSee('25.000 ₫');
    }

    public function test_staff_can_advance_order_status_and_history_is_recorded(): void
    {
        $orderId = $this->createOrder(['ma_don_hang' => 'ORD-UPDATE']);

        $this->put(route('admin.orders.update', $orderId), [
            'trang_thai' => Order::STATUS_PROCESSING,
            'ghi_chu' => 'Đã xác nhận với khách.',
        ])->assertRedirect(route('admin.orders.show', $orderId));

        $this->assertDatabaseHas('don_hangs', [
            'id' => $orderId,
            'trang_thai' => Order::STATUS_PROCESSING,
        ]);
        $this->assertDatabaseHas('lich_su_trang_thai_don_hangs', [
            'don_hang_id' => $orderId,
            'trang_thai' => Order::STATUS_PROCESSING,
            'ghi_chu' => 'Đã xác nhận với khách.',
        ]);

        $this->put(route('admin.orders.update', $orderId), [
            'trang_thai' => Order::STATUS_SHIPPING,
        ])->assertRedirect(route('admin.orders.show', $orderId));

        $this->assertDatabaseHas('don_hangs', [
            'id' => $orderId,
            'trang_thai' => Order::STATUS_SHIPPING,
        ]);
    }

    public function test_order_status_transition_cannot_skip_steps_or_modify_completed_order(): void
    {
        $orderId = $this->createOrder(['trang_thai' => Order::STATUS_PENDING]);

        $this->from(route('admin.orders.show', $orderId))
            ->put(route('admin.orders.update', $orderId), [
                'trang_thai' => Order::STATUS_SHIPPING,
            ])
            ->assertRedirect(route('admin.orders.show', $orderId))
            ->assertSessionHasErrors('trang_thai');

        $this->assertDatabaseHas('don_hangs', [
            'id' => $orderId,
            'trang_thai' => Order::STATUS_PENDING,
        ]);

        DB::table('don_hangs')->where('id', $orderId)->update([
            'trang_thai' => Order::STATUS_COMPLETED,
        ]);

        $this->from(route('admin.orders.show', $orderId))
            ->put(route('admin.orders.update', $orderId), [
                'trang_thai' => Order::STATUS_CANCELLED,
            ])
            ->assertRedirect(route('admin.orders.show', $orderId))
            ->assertSessionHasErrors('trang_thai');
    }

    private function createOrder(array $attributes = []): int
    {
        return DB::table('don_hangs')->insertGetId(array_merge([
            'ma_don_hang' => 'ORD-'.uniqid(),
            'ten_nguoi_nhan' => 'Khách hàng thử nghiệm',
            'ngay_dat' => now(),
            'tien_hang' => 500000,
            'phi_van_chuyen' => 30000,
            'tien_giam' => 0,
            'tong_tien' => 530000,
            'trang_thai_thanh_toan' => Order::PAYMENT_UNPAID,
            'trang_thai' => Order::STATUS_PENDING,
        ], $attributes));
    }
}
