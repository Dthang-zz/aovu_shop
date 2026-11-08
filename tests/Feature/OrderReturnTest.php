<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-08 12:00:00');

        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ho_ten')->nullable();
            $table->string('email')->nullable();
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
            $table->text('ghi_chu')->nullable();
            $table->dateTime('ngay_dat')->nullable();
            $table->decimal('tien_hang', 12, 0)->nullable();
            $table->decimal('phi_van_chuyen', 12, 0)->nullable();
            $table->decimal('tien_giam', 12, 0)->nullable();
            $table->decimal('tien_giam_voucher', 12, 0)->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
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
            $table->string('ten_khuyen_mai')->nullable();
            $table->decimal('phan_tram_giam', 5, 2)->nullable();
        });

        DB::table('danh_mucs')->insert(['id' => 1, 'ten_danh_muc' => 'Áo']);
        DB::table('san_phams')->insert(['id' => 10, 'ma_san_pham' => 'SP-10', 'ten_san_pham' => 'Áo Linen', 'danh_muc_id' => 1]);
    }

    public function test_completed_order_within_window_shows_return_form_and_creates_request(): void
    {
        [$orderId, $itemId] = $this->createCompletedOrder();

        $this->get(route('admin.orders.show', $orderId))
            ->assertOk()
            ->assertSee('Đổi / trả hàng')
            ->assertSee('Hạn đổi/trả: <strong>13/11/2026</strong>', false)
            ->assertSee('action="'.route('admin.orders.returns.store', $orderId).'"', false);

        $this->post(route('admin.orders.returns.store', $orderId), [
            'chi_tiet_don_hang_id' => $itemId,
            'loai_yeu_cau' => ReturnRequest::TYPE_RETURN,
            'so_luong' => 1,
            'ly_do' => 'Áo bị lỗi đường may',
        ])->assertRedirect(route('admin.orders.show', $orderId).'#returns')
            ->assertSessionHas('return_success');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'don_hang_id' => $orderId,
            'chi_tiet_don_hang_id' => $itemId,
            'tai_khoan_id' => 5,
            'loai_yeu_cau' => ReturnRequest::TYPE_RETURN,
            'so_luong' => 1,
            'trang_thai' => ReturnRequest::STATUS_PENDING,
        ]);
        $this->get(route('admin.orders.show', $orderId))
            ->assertSee('Áo bị lỗi đường may')
            ->assertSee('Chờ duyệt');
    }

    public function test_return_form_is_hidden_and_creation_rejected_for_unfinished_or_expired_orders(): void
    {
        [$pendingOrderId, $pendingItemId] = $this->createCompletedOrder(['trang_thai' => Order::STATUS_SHIPPING, 'ngay_hoan_thanh' => null]);
        [$expiredOrderId, $expiredItemId] = $this->createCompletedOrder(['ngay_hoan_thanh' => '2026-10-20 10:00:00']);

        $this->get(route('admin.orders.show', $pendingOrderId))
            ->assertOk()
            ->assertDontSee('action="'.route('admin.orders.returns.store', $pendingOrderId).'"', false)
            ->assertSee('Có thể tạo yêu cầu đổi/trả sau khi đơn hàng hoàn thành.');
        $this->get(route('admin.orders.show', $expiredOrderId))
            ->assertOk()
            ->assertDontSee('action="'.route('admin.orders.returns.store', $expiredOrderId).'"', false)
            ->assertSee('Đã quá hạn đổi/trả');

        foreach ([$pendingOrderId => $pendingItemId, $expiredOrderId => $expiredItemId] as $orderId => $itemId) {
            $this->post(route('admin.orders.returns.store', $orderId), $this->requestPayload($itemId))
                ->assertSessionHasErrorsIn('returns', 'chi_tiet_don_hang_id');
        }

        $this->assertDatabaseCount('yeu_cau_doi_tras', 0);
    }

    public function test_return_request_validates_reason_item_ownership_and_remaining_quantity(): void
    {
        [$orderId, $itemId] = $this->createCompletedOrder();
        [, $otherOrderItemId] = $this->createCompletedOrder();
        DB::table('yeu_cau_doi_tras')->insert([
            ['don_hang_id' => $orderId, 'chi_tiet_don_hang_id' => $itemId, 'loai_yeu_cau' => 2, 'so_luong' => 1, 'trang_thai' => ReturnRequest::STATUS_PENDING],
            ['don_hang_id' => $orderId, 'chi_tiet_don_hang_id' => $itemId, 'loai_yeu_cau' => 2, 'so_luong' => 1, 'trang_thai' => ReturnRequest::STATUS_REJECTED],
        ]);

        $this->post(route('admin.orders.returns.store', $orderId), [...$this->requestPayload($itemId), 'ly_do' => ''])
            ->assertSessionHasErrorsIn('returns', 'ly_do');
        $this->post(route('admin.orders.returns.store', $orderId), $this->requestPayload($otherOrderItemId))
            ->assertSessionHasErrorsIn('returns', 'chi_tiet_don_hang_id');
        $this->post(route('admin.orders.returns.store', $orderId), [...$this->requestPayload($itemId), 'so_luong' => 2])
            ->assertSessionHasErrorsIn('returns', ['so_luong' => 'Số lượng vượt quá số sản phẩm còn có thể đổi/trả (1).']);

        $this->assertDatabaseCount('yeu_cau_doi_tras', 2);
    }

    public function test_return_goes_through_approve_receive_and_complete_with_refund_and_restock(): void
    {
        $variant = $this->createVariant(stock: 3);
        [$orderId, $itemId] = $this->createCompletedOrder(itemAttributes: ['product_variant_id' => $variant->id]);
        $returnId = $this->createReturnRequest($orderId, $itemId, ReturnRequest::TYPE_RETURN, quantity: 2);

        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'complete', 'so_tien_hoan' => 500000])
            ->assertSessionHasErrorsIn('returns', 'thao_tac');

        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'approve'])
            ->assertRedirect(route('admin.orders.show', $orderId).'#returns');
        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'receive']);
        $this->assertDatabaseHas('yeu_cau_doi_tras', ['id' => $returnId, 'trang_thai' => ReturnRequest::STATUS_RETURN_RECEIVED, 'ngay_nhan_hang' => '2026-11-08 12:00:00']);

        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'complete', 'so_tien_hoan' => 500001])
            ->assertSessionHasErrorsIn('returns', ['so_tien_hoan' => 'Số tiền hoàn vượt quá giá trị hàng trả (500.000 ₫).']);
        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'complete'])
            ->assertSessionHasErrorsIn('returns', 'so_tien_hoan');
        $this->assertSame(3, $variant->fresh()->stock);

        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), [
            'thao_tac' => 'complete',
            'so_tien_hoan' => 500000,
            'ma_giao_dich_hoan' => 'FT123',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $returnId,
            'trang_thai' => ReturnRequest::STATUS_COMPLETED,
            'so_tien_hoan' => 500000,
            'ma_giao_dich_hoan' => 'FT123',
            'ngay_hoan_tat' => '2026-11-08 12:00:00',
        ]);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->get(route('admin.orders.show', $orderId))->assertSee('500.000 ₫')->assertSee('Mã GD: FT123');
    }

    public function test_refund_is_capped_at_the_value_paid_after_the_order_voucher(): void
    {
        [$orderId, $itemId] = $this->createCompletedOrder(['tien_giam_voucher' => 50000]);
        $returnId = $this->createReturnRequest($orderId, $itemId, ReturnRequest::TYPE_RETURN, quantity: 1, status: ReturnRequest::STATUS_RETURN_RECEIVED);

        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'complete', 'so_tien_hoan' => 225001])
            ->assertSessionHasErrorsIn('returns', ['so_tien_hoan' => 'Số tiền hoàn vượt quá giá trị hàng trả (225.000 ₫).']);
    }

    public function test_exchange_completion_requires_replacement_with_stock_and_moves_stock_between_variants(): void
    {
        $original = $this->createVariant(stock: 0, sku: 'LINEN-M');
        $outOfStock = $this->createVariant(stock: 1, sku: 'LINEN-XL');
        $replacement = $this->createVariant(stock: 4, sku: 'LINEN-L');
        [$orderId, $itemId] = $this->createCompletedOrder(itemAttributes: ['product_variant_id' => $original->id]);
        $returnId = $this->createReturnRequest($orderId, $itemId, ReturnRequest::TYPE_EXCHANGE, quantity: 2, status: ReturnRequest::STATUS_RETURN_RECEIVED);

        $this->get(route('admin.orders.show', $orderId))
            ->assertSee('Gửi hàng đổi &amp; hoàn tất', false)
            ->assertSee('LINEN-L');

        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'complete'])
            ->assertSessionHasErrorsIn('returns', 'bien_the_thay_the_id');
        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'complete', 'bien_the_thay_the_id' => $outOfStock->id])
            ->assertSessionHasErrorsIn('returns', 'bien_the_thay_the_id');

        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'complete', 'bien_the_thay_the_id' => $replacement->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $returnId,
            'trang_thai' => ReturnRequest::STATUS_COMPLETED,
            'bien_the_thay_the_id' => $replacement->id,
            'so_tien_hoan' => null,
        ]);
        $this->assertSame(2, $original->fresh()->stock);
        $this->assertSame(2, $replacement->fresh()->stock);
        $this->assertSame(1, $outOfStock->fresh()->stock);
    }

    public function test_rejection_requires_a_reason_and_rejecting_after_receipt_does_not_restock(): void
    {
        $variant = $this->createVariant(stock: 3);
        [$orderId, $itemId] = $this->createCompletedOrder(itemAttributes: ['product_variant_id' => $variant->id]);
        $returnId = $this->createReturnRequest($orderId, $itemId, ReturnRequest::TYPE_RETURN, quantity: 1, status: ReturnRequest::STATUS_RETURN_RECEIVED);

        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'reject', 'phan_hoi' => ' '])
            ->assertSessionHasErrorsIn('returns', 'phan_hoi');

        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'reject', 'phan_hoi' => 'Sản phẩm đã qua sử dụng'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('yeu_cau_doi_tras', ['id' => $returnId, 'trang_thai' => ReturnRequest::STATUS_REJECTED, 'phan_hoi' => 'Sản phẩm đã qua sử dụng']);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->patch(route('admin.orders.returns.update', [$orderId, $returnId]), ['thao_tac' => 'approve'])
            ->assertSessionHasErrorsIn('returns', 'thao_tac');
    }

    public function test_request_cannot_be_updated_through_another_order(): void
    {
        [$orderId, $itemId] = $this->createCompletedOrder();
        [$otherOrderId] = $this->createCompletedOrder();
        $returnId = $this->createReturnRequest($orderId, $itemId, ReturnRequest::TYPE_RETURN, quantity: 1);

        $this->patch(route('admin.orders.returns.update', [$otherOrderId, $returnId]), ['thao_tac' => 'approve'])
            ->assertNotFound();
        $this->assertDatabaseHas('yeu_cau_doi_tras', ['id' => $returnId, 'trang_thai' => ReturnRequest::STATUS_PENDING]);
    }

    public function test_order_list_flags_and_filters_orders_with_pending_returns(): void
    {
        [$orderId, $itemId] = $this->createCompletedOrder(['ma_don_hang' => 'ORD-RETURN']);
        $this->createCompletedOrder(['ma_don_hang' => 'ORD-PLAIN']);
        $this->createReturnRequest($orderId, $itemId, ReturnRequest::TYPE_EXCHANGE, quantity: 1);

        $this->get(route('admin.orders.index', ['return_status' => 'pending']))
            ->assertOk()
            ->assertSee('ORD-RETURN')
            ->assertSee('Đổi/trả chờ duyệt')
            ->assertDontSee('ORD-PLAIN');
    }

    public function test_old_return_and_refund_wallet_urls_redirect_to_orders(): void
    {
        [$orderId, $itemId] = $this->createCompletedOrder();
        $returnId = $this->createReturnRequest($orderId, $itemId, ReturnRequest::TYPE_RETURN, quantity: 1);
        $pendingReturnsUrl = route('admin.orders.index', ['return_status' => 'pending']);

        $this->get('/admin/returns')->assertRedirect($pendingReturnsUrl);
        $this->get('/admin/finance/refunds')->assertRedirect($pendingReturnsUrl);
        $this->get('/admin/returns/'.$returnId)->assertRedirect(route('admin.orders.show', $orderId).'#returns');
        $this->get('/admin/returns/'.$returnId.'/edit')->assertRedirect(route('admin.orders.show', $orderId).'#returns');
        $this->get('/admin/returns/999999')->assertNotFound();
        $this->post('/admin/returns')->assertStatus(405);
        $this->assertFalse(Schema::hasTable('support_return_workflows'));
        $this->assertFalse(Schema::hasTable('support_return_histories'));
    }

    public function test_migration_moves_workflow_refunds_onto_return_requests_and_maps_old_statuses(): void
    {
        $migration = require database_path('migrations/2026_11_08_155745_simplify_return_requests_into_orders.php');
        Schema::create('support_return_workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('return_request_id');
            $table->unsignedInteger('replacement_variant_id')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->decimal('refund_amount', 12, 0)->nullable();
            $table->string('refund_reference', 100)->nullable();
            $table->dateTime('refunded_at')->nullable();
        });
        Schema::create('support_return_histories', function (Blueprint $table) {
            $table->id();
        });
        foreach ([1 => 1, 2 => 5, 3 => 6, 4 => 8, 5 => 9, 6 => 3] as $id => $oldStatus) {
            DB::table('yeu_cau_doi_tras')->insert(['id' => $id, 'loai_yeu_cau' => 2, 'so_luong' => 1, 'trang_thai' => $oldStatus]);
        }
        DB::table('support_return_workflows')->insert([
            ['return_request_id' => 2, 'replacement_variant_id' => null, 'received_at' => '2026-11-01 09:00:00', 'delivered_at' => null, 'refund_amount' => 459000, 'refund_reference' => 'FT-OLD', 'refunded_at' => '2026-11-02 09:00:00'],
            ['return_request_id' => 5, 'replacement_variant_id' => 7, 'received_at' => null, 'delivered_at' => '2026-11-03 09:00:00', 'refund_amount' => null, 'refund_reference' => null, 'refunded_at' => null],
        ]);

        $migration->up();

        $this->assertSame(
            [1 => 0, 2 => 4, 3 => 2, 4 => 7, 5 => 4, 6 => 3],
            DB::table('yeu_cau_doi_tras')->orderBy('id')->pluck('trang_thai', 'id')->map(fn ($status): int => (int) $status)->all(),
        );
        $this->assertDatabaseHas('yeu_cau_doi_tras', ['id' => 2, 'so_tien_hoan' => 459000, 'ma_giao_dich_hoan' => 'FT-OLD', 'ngay_nhan_hang' => '2026-11-01 09:00:00', 'ngay_hoan_tat' => '2026-11-02 09:00:00']);
        $this->assertDatabaseHas('yeu_cau_doi_tras', ['id' => 5, 'bien_the_thay_the_id' => 7, 'ngay_hoan_tat' => '2026-11-03 09:00:00']);
        $this->assertFalse(Schema::hasTable('support_return_workflows'));
        $this->assertFalse(Schema::hasTable('support_return_histories'));
    }

    /**
     * @param  array<string, mixed>  $orderAttributes
     * @param  array<string, mixed>  $itemAttributes
     * @return array{0: int, 1: int}
     */
    private function createCompletedOrder(array $orderAttributes = [], array $itemAttributes = []): array
    {
        $orderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'ORD-'.uniqid(),
            'tai_khoan_id' => 5,
            'ten_nguoi_nhan' => 'Khách đổi trả',
            'ngay_dat' => '2026-11-01 10:00:00',
            'tien_hang' => 500000,
            'phi_van_chuyen' => 0,
            'tien_giam' => 0,
            'tong_tien' => 500000,
            'trang_thai_thanh_toan' => Order::PAYMENT_PAID,
            'trang_thai' => Order::STATUS_COMPLETED,
            'ngay_hoan_thanh' => '2026-11-06 10:00:00',
            ...$orderAttributes,
        ]);
        $itemId = DB::table('chi_tiet_don_hangs')->insertGetId([
            'don_hang_id' => $orderId,
            'ten_san_pham' => 'Áo Linen (M)',
            'don_gia' => 250000,
            'so_luong' => 2,
            'thanh_tien' => 500000,
            ...$itemAttributes,
        ]);

        return [$orderId, $itemId];
    }

    private function createReturnRequest(int $orderId, int $itemId, int $type, int $quantity, int $status = ReturnRequest::STATUS_PENDING): int
    {
        return DB::table('yeu_cau_doi_tras')->insertGetId([
            'don_hang_id' => $orderId,
            'chi_tiet_don_hang_id' => $itemId,
            'tai_khoan_id' => 5,
            'loai_yeu_cau' => $type,
            'so_luong' => $quantity,
            'ly_do' => 'Không vừa size',
            'ngay_tao' => '2026-11-07 10:00:00',
            'trang_thai' => $status,
        ]);
    }

    private function createVariant(int $stock, string $sku = 'LINEN-M'): ProductVariant
    {
        return ProductVariant::query()->create([
            'product_id' => 10,
            'sku' => $sku,
            'price' => 250000,
            'stock' => $stock,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);
    }

    /**
     * @return array{chi_tiet_don_hang_id: int, loai_yeu_cau: int, so_luong: int, ly_do: string}
     */
    private function requestPayload(int $itemId): array
    {
        return [
            'chi_tiet_don_hang_id' => $itemId,
            'loai_yeu_cau' => ReturnRequest::TYPE_EXCHANGE,
            'so_luong' => 1,
            'ly_do' => 'Muốn đổi size',
        ];
    }
}
