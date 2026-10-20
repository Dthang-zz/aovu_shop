<?php

namespace Tests\Feature;

use App\OrderStatus;
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

        if (! Schema::hasTable('tai_khoans')) {
            Schema::create('tai_khoans', function (Blueprint $table) {
                $table->increments('id');
                $table->string('ho_ten')->nullable();
                $table->string('email')->nullable();
            });
        }
    }

    public function test_admin_can_search_and_filter_orders(): void
    {
        $this->createOrder([
            'ma_don_hang' => 'ORD-SEARCH-1',
            'ten_nguoi_nhan' => 'Nguyen Van An',
            'ngay_dat' => '2026-10-20 10:00:00',
        ]);
        $this->createOrder([
            'ma_don_hang' => 'ORD-OTHER-2',
            'ten_nguoi_nhan' => 'Tran Thi Binh',
            'trang_thai' => OrderStatus::Shipping->value,
            'ngay_dat' => '2026-10-19 10:00:00',
        ]);

        $this->get(route('admin.orders.index', [
            'q' => 'ORD-SEARCH',
            'status' => (string) OrderStatus::Pending->value,
            'from' => '2026-10-20',
            'to' => '2026-10-20',
        ]))
            ->assertOk()
            ->assertSee('ORD-SEARCH-1')
            ->assertSee('Nguyen Van An')
            ->assertDontSee('ORD-OTHER-2');

    }

    public function test_admin_can_update_and_cancel_orders_with_audited_reason(): void
    {
        $orderId = $this->createOrder();

        $this->put(route('admin.orders.update', $orderId), [
            'trang_thai' => OrderStatus::Processing->value,
            'ghi_chu' => 'Đã xác nhận đơn.',
        ])->assertRedirect(route('admin.orders.show', $orderId));

        $this->assertDatabaseHas('don_hangs', [
            'id' => $orderId,
            'trang_thai' => OrderStatus::Processing->value,
        ]);
        $this->assertDatabaseHas('lich_su_trang_thai_don_hangs', [
            'don_hang_id' => $orderId,
            'trang_thai' => OrderStatus::Processing->value,
            'ghi_chu' => 'Đã xác nhận đơn.',
        ]);

        $this->from(route('admin.orders.show', $orderId))
            ->put(route('admin.orders.update', $orderId), [
                'trang_thai' => OrderStatus::Cancelled->value,
                'ghi_chu' => '',
            ])
            ->assertRedirect(route('admin.orders.show', $orderId))
            ->assertSessionHasErrors('ghi_chu');

        $this->assertDatabaseHas('don_hangs', [
            'id' => $orderId,
            'trang_thai' => OrderStatus::Processing->value,
        ]);

        $this->put(route('admin.orders.update', $orderId), [
            'trang_thai' => OrderStatus::Cancelled->value,
            'ghi_chu' => 'Khách yêu cầu hủy.',
        ])->assertRedirect(route('admin.orders.show', $orderId));

        $this->assertDatabaseHas('don_hangs', [
            'id' => $orderId,
            'trang_thai' => OrderStatus::Cancelled->value,
        ]);
        $this->assertDatabaseHas('lich_su_trang_thai_don_hangs', [
            'don_hang_id' => $orderId,
            'trang_thai' => OrderStatus::Cancelled->value,
            'ghi_chu' => 'Khách yêu cầu hủy.',
        ]);

        $this->get(route('admin.orders.show', $orderId))
            ->assertOk()
            ->assertSee('Khách yêu cầu hủy.')
            ->assertSee('chưa tự chuyển tiền hoặc điều chỉnh tồn kho');
    }

    public function test_admin_cannot_skip_order_statuses_or_reopen_a_cancelled_order(): void
    {
        $orderId = $this->createOrder();

        $this->from(route('admin.orders.show', $orderId))
            ->put(route('admin.orders.update', $orderId), [
                'trang_thai' => OrderStatus::Completed->value,
            ])
            ->assertRedirect(route('admin.orders.show', $orderId))
            ->assertSessionHasErrors('trang_thai');

        $this->assertDatabaseHas('don_hangs', [
            'id' => $orderId,
            'trang_thai' => OrderStatus::Pending->value,
        ]);

        $cancelledOrderId = $this->createOrder([
            'ma_don_hang' => 'ORD-CANCELLED',
            'trang_thai' => OrderStatus::Cancelled->value,
        ]);

        $this->from(route('admin.orders.show', $cancelledOrderId))
            ->put(route('admin.orders.update', $cancelledOrderId), [
                'trang_thai' => OrderStatus::Processing->value,
            ])
            ->assertRedirect(route('admin.orders.show', $cancelledOrderId))
            ->assertSessionHasErrors('trang_thai');

        $this->assertDatabaseHas('don_hangs', [
            'id' => $cancelledOrderId,
            'trang_thai' => OrderStatus::Cancelled->value,
        ]);
    }

    public function test_customer_can_track_an_order_with_its_code_and_contact(): void
    {
        $orderId = $this->createOrder([
            'ma_don_hang' => 'ORD-TRACK-1',
            'ten_nguoi_nhan' => 'Khach mua hang',
            'email_nguoi_nhan' => 'buyer@example.test',
            'sdt_nguoi_nhan' => '0901234567',
            'trang_thai' => OrderStatus::Shipping->value,
        ]);

        DB::table('chi_tiet_don_hangs')->insert([
            'don_hang_id' => $orderId,
            'ten_san_pham' => 'Ao so mi',
            'don_gia' => 250000,
            'so_luong' => 2,
            'thanh_tien' => 500000,
        ]);
        DB::table('lich_su_trang_thai_don_hangs')->insert([
            'don_hang_id' => $orderId,
            'trang_thai' => OrderStatus::Shipping->value,
            'ghi_chu' => 'Đơn hàng đang trên đường giao.',
            'ngay_cap_nhat' => '2026-10-20 12:00:00',
        ]);

        $this->get(route('orders.track'))->assertOk()->assertSee('Tra cứu đơn hàng');

        $this->post(route('orders.track.lookup'), [
            'ma_don_hang' => 'ORD-TRACK-1',
            'lien_he' => '0901234567',
        ])
            ->assertOk()
            ->assertSee('ORD-TRACK-1')
            ->assertSee('Đang giao')
            ->assertSee('Ao so mi')
            ->assertSee('Đơn hàng đang trên đường giao.')
            ->assertDontSee('buyer@example.test')
            ->assertDontSee('0901234567');

        $this->post(route('orders.track.lookup'), [
            'ma_don_hang' => 'ORD-TRACK-1',
            'lien_he' => 'wrong@example.test',
        ])
            ->assertOk()
            ->assertSee('Không tìm thấy đơn hàng')
            ->assertDontSee('Ao so mi');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrder(array $overrides = []): int
    {
        return (int) DB::table('don_hangs')->insertGetId(array_merge([
            'ma_don_hang' => 'ORD-DEFAULT',
            'ten_nguoi_nhan' => 'Khach hang',
            'email_nguoi_nhan' => 'customer@example.test',
            'sdt_nguoi_nhan' => '0900000000',
            'dia_chi_nguoi_nhan' => 'Quan 1, TP HCM',
            'ngay_dat' => '2026-10-20 10:00:00',
            'tien_hang' => 500000,
            'phi_van_chuyen' => 30000,
            'tien_giam' => 0,
            'tong_tien' => 530000,
            'trang_thai' => OrderStatus::Pending->value,
        ], $overrides));
    }
}
