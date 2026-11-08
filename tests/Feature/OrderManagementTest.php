<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
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
            $table->dateTime('ngay_hoan_thanh')->nullable();
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
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

    public function test_order_detail_page_has_status_form_with_only_the_allowed_next_statuses(): void
    {
        $orderId = $this->createOrder(['ma_don_hang' => 'ORD-STATUS', 'trang_thai' => Order::STATUS_PENDING]);

        $this->get(route('admin.orders.show', $orderId))
            ->assertOk()
            ->assertSee('ORD-STATUS')
            ->assertSee('action="'.route('admin.orders.update', $orderId).'"', false)
            ->assertSee('<option value="'.Order::STATUS_PROCESSING.'"', false)
            ->assertSee('<option value="'.Order::STATUS_CANCELLED.'"', false)
            ->assertDontSee('<option value="'.Order::STATUS_COMPLETED.'"', false)
            ->assertSee('name="ghi_chu"', false);
    }

    public function test_finished_order_detail_page_has_no_status_form(): void
    {
        $orderId = $this->createOrder(['trang_thai' => Order::STATUS_COMPLETED]);

        $this->get(route('admin.orders.show', $orderId))
            ->assertOk()
            ->assertDontSee('name="trang_thai"', false)
            ->assertSee('Đơn hàng đã kết thúc, không thể cập nhật thêm.');
    }

    public function test_order_edit_page_no_longer_exists(): void
    {
        $orderId = $this->createOrder(['trang_thai' => Order::STATUS_PENDING]);

        $this->assertFalse(Route::has('admin.orders.edit'));
        $this->get('/admin/orders/'.$orderId.'/edit')->assertNotFound();
        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertDontSee('/admin/orders/'.$orderId.'/edit');
    }

    public function test_order_list_and_detail_show_both_orderer_and_recipient(): void
    {
        $accountId = DB::table('tai_khoans')->insertGetId(['ho_ten' => 'Trần Thị Đặt']);
        $orderId = $this->createOrder([
            'ma_don_hang' => 'ORD-GIFT',
            'tai_khoan_id' => $accountId,
            'ten_nguoi_nhan' => 'Lê Văn Nhận',
            'sdt_nguoi_nhan' => '0901234567',
            'dia_chi_nguoi_nhan' => '12 Lý Thường Kiệt, Hà Nội',
        ]);
        $this->createOrder(['ma_don_hang' => 'ORD-GUEST', 'tai_khoan_id' => null, 'ten_nguoi_nhan' => 'Khách lẻ']);

        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSeeInOrder(['Người đặt', 'Người nhận'])
            ->assertSee('Trần Thị Đặt')
            ->assertSee('Lê Văn Nhận')
            ->assertSee('Khách vãng lai');

        $this->get(route('admin.orders.show', $orderId))
            ->assertOk()
            ->assertSeeInOrder(['Người đặt', 'Trần Thị Đặt', 'Người nhận', 'Lê Văn Nhận', '0901234567', '12 Lý Thường Kiệt, Hà Nội'])
            ->assertSee(route('admin.users.show', $accountId));
    }

    public function test_completing_a_cod_order_marks_it_paid_and_records_completion_date(): void
    {
        $this->travelTo('2026-11-03 10:00:00');
        $orderId = $this->createOrder(['trang_thai' => Order::STATUS_SHIPPING]);

        $this->put(route('admin.orders.update', $orderId), [
            'trang_thai' => Order::STATUS_COMPLETED,
        ])->assertRedirect(route('admin.orders.show', $orderId));

        $this->assertDatabaseHas('don_hangs', [
            'id' => $orderId,
            'trang_thai' => Order::STATUS_COMPLETED,
            'trang_thai_thanh_toan' => Order::PAYMENT_PAID,
            'ngay_hoan_thanh' => '2026-11-03 10:00:00',
        ]);
    }

    public function test_cancelling_an_order_returns_variant_stock(): void
    {
        DB::table('danh_mucs')->insert(['id' => 1, 'ten_danh_muc' => 'Áo']);
        DB::table('san_phams')->insert(['id' => 10, 'ma_san_pham' => 'SP-10', 'ten_san_pham' => 'Áo Linen', 'danh_muc_id' => 1]);
        $variant = ProductVariant::query()->create([
            'product_id' => 10,
            'sku' => 'LINEN-M',
            'price' => 250000,
            'stock' => 3,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);
        $orderId = $this->createOrder(['trang_thai' => Order::STATUS_PROCESSING]);
        DB::table('chi_tiet_don_hangs')->insert([
            ['don_hang_id' => $orderId, 'product_variant_id' => $variant->id, 'ten_san_pham' => 'Áo Linen (M)', 'don_gia' => 250000, 'so_luong' => 2, 'thanh_tien' => 500000],
            ['don_hang_id' => $orderId, 'product_variant_id' => null, 'ten_san_pham' => 'Tất', 'don_gia' => 30000, 'so_luong' => 1, 'thanh_tien' => 30000],
        ]);

        $this->put(route('admin.orders.update', $orderId), [
            'trang_thai' => Order::STATUS_CANCELLED,
        ])->assertRedirect(route('admin.orders.show', $orderId));

        $this->assertDatabaseHas('don_hangs', ['id' => $orderId, 'trang_thai' => Order::STATUS_CANCELLED]);
        $this->assertSame(5, $variant->fresh()->stock);
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
