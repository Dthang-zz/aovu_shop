<?php

namespace Tests\Feature;

use App\Models\ReturnRequest;
use App\Models\ReturnRequestWorkflow;
use App\Models\SupportConversation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReturnRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang')->nullable();
            $table->string('ten_nguoi_nhan')->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
            $table->unsignedInteger('tai_khoan_id')->nullable();
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->unsignedInteger('bien_the_id')->nullable();
            $table->string('ten_san_pham')->nullable();
            $table->decimal('don_gia', 12, 0)->nullable();
            $table->decimal('thanh_tien', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
        });
        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ho_ten')->nullable();
            $table->string('email')->nullable();
            $table->string('so_dien_thoai')->nullable();
        });
        if (! Schema::hasTable('mau_sacs')) {
            Schema::create('mau_sacs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('ten_mau')->nullable();
            });
        }
        if (! Schema::hasTable('kich_thuocs')) {
            Schema::create('kich_thuocs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('ten_kich_thuoc')->nullable();
            });
        }
        if (! Schema::hasTable('bien_thes')) {
            Schema::create('bien_thes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('san_pham_id')->nullable();
                $table->unsignedInteger('mau_sac_id')->nullable();
                $table->unsignedInteger('kich_thuoc_id')->nullable();
                $table->string('sku')->nullable();
                $table->decimal('gia_bien_the', 12, 0)->nullable();
                $table->integer('so_luong_ton')->nullable();
                $table->unsignedTinyInteger('trang_thai')->nullable();
            });
        }
    }

    public function test_return_request_list_shows_joined_details_and_filters(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(
            ReturnRequest::TYPE_RETURN,
            ReturnRequest::STATUS_PENDING,
            'Đường may bị lỗi'
        );
        $this->createRequest(
            ReturnRequest::TYPE_EXCHANGE,
            ReturnRequest::STATUS_PROCESSING,
            'Cần đổi sang kích thước lớn hơn'
        );

        $this->get(route('admin.returns.index', [
            'q' => 'ORD-100',
            'type' => (string) ReturnRequest::TYPE_RETURN,
            'status' => (string) ReturnRequest::STATUS_PENDING,
        ]))
            ->assertOk()
            ->assertSee('#DT-'.str_pad((string) $requestId, 6, '0', STR_PAD_LEFT))
            ->assertSee('ORD-100')
            ->assertSee('Khách hàng thử nghiệm')
            ->assertSee('Áo Linen')
            ->assertSee('Đường may bị lỗi')
            ->assertDontSee('Cần đổi sang kích thước lớn hơn');
    }

    public function test_staff_can_create_a_manual_return_request_from_the_returns_section(): void
    {
        $this->seedOrderDetails();

        $this->get(route('admin.returns.index'))
            ->assertOk()
            ->assertSee('Tạo phiếu đổi / trả');
        $this->get(route('admin.returns.create'))
            ->assertOk()
            ->assertSee('Lập yêu cầu thủ công')
            ->assertSee(route('admin.returns.store'));

        $this->post(route('admin.returns.store'), [
            'customer_id' => 300,
            'order_id' => 100,
            'chi_tiet_don_hang_id' => 200,
            'loai_yeu_cau' => ReturnRequest::TYPE_RETURN,
            'so_luong' => 1,
            'ly_do' => 'Khách báo sản phẩm bị lỗi.',
        ])->assertRedirect(route('admin.returns.show', 1))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => 1,
            'don_hang_id' => 100,
            'chi_tiet_don_hang_id' => 200,
            'tai_khoan_id' => 300,
            'loai_yeu_cau' => ReturnRequest::TYPE_RETURN,
            'so_luong' => 1,
            'trang_thai' => ReturnRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('support_return_workflows', [
            'return_request_id' => 1,
            'conversation_id' => null,
        ]);
        $this->assertDatabaseHas('support_return_histories', [
            'return_request_id' => 1,
            'from_status' => null,
            'to_status' => ReturnRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseCount('support_messages', 0);
    }

    public function test_staff_can_inspect_return_but_cannot_mark_it_refunded_without_financial_controls(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_PENDING);
        $conversation = $this->createConversation();
        ReturnRequestWorkflow::query()->create([
            'return_request_id' => $requestId,
            'conversation_id' => $conversation->id,
        ]);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_PROCESSING,
            'phan_hoi' => 'Đã tiếp nhận yêu cầu và đang kiểm tra.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_PROCESSING,
            'phan_hoi' => 'Đã tiếp nhận yêu cầu và đang kiểm tra.',
        ]);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_APPROVED,
            'phan_hoi' => 'Sản phẩm đạt điều kiện trả hàng.',
            'customer_agreed' => 1,
            'policy_reviewed' => 1,
            'policy_notes' => 'Yêu cầu trong thời hạn đổi trả.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_AWAITING_RETURN,
            'phan_hoi' => 'Vui lòng gửi sản phẩm về cửa hàng.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_RETURN_RECEIVED,
            'phan_hoi' => 'Cửa hàng đã nhận sản phẩm.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_INSPECTED,
            'inspection_notes' => 'Sản phẩm lỗi đường may, đủ điều kiện hoàn.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->from(route('admin.returns.show', $requestId))
            ->put(route('admin.returns.update', $requestId), [
                'trang_thai' => ReturnRequest::STATUS_REFUNDED,
                'phan_hoi' => 'Đã hoàn tiền theo quy trình kế toán.',
                'refund_amount' => 250000,
                'refund_method' => 'bank_transfer',
                'refund_reference' => 'REFUND-100',
            ])
            ->assertRedirect(route('admin.returns.show', $requestId))
            ->assertSessionHasErrors('trang_thai');

        $this->get(route('admin.returns.show', $requestId))
            ->assertOk()
            ->assertSee('Đã kiểm tra hàng')
            ->assertDontSee('Đã hoàn tiền theo quy trình kế toán.')
            ->assertSee('Lịch sử xử lý');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_INSPECTED,
        ]);
        $this->assertDatabaseMissing('support_return_workflows', [
            'return_request_id' => $requestId,
            'refund_amount' => 250000,
        ]);
        $this->assertDatabaseCount('support_return_histories', 5);
        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conversation->id,
            'body' => 'Phiếu #DT-000001 (Trả hàng) đã cập nhật: Đã kiểm tra hàng.',
        ]);
        $this->get(route('admin.returns.index'))
            ->assertOk()
            ->assertSee('Đã kiểm tra hàng');
    }

    public function test_rejection_requires_customer_feedback(): void
    {
        $requestId = $this->createRequest(ReturnRequest::TYPE_EXCHANGE, ReturnRequest::STATUS_PENDING);

        $this->from(route('admin.returns.show', $requestId))
            ->put(route('admin.returns.update', $requestId), [
                'trang_thai' => ReturnRequest::STATUS_REJECTED,
                'phan_hoi' => '',
            ])
            ->assertRedirect(route('admin.returns.show', $requestId))
            ->assertSessionHasErrors('phan_hoi');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_PENDING,
        ]);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_REJECTED,
            'phan_hoi' => 'Yêu cầu nằm ngoài thời hạn đổi hàng.',
        ])->assertRedirect(route('admin.returns.show', $requestId));
    }

    public function test_status_transitions_are_restricted_and_exchange_cannot_be_refunded(): void
    {
        $exchangeId = $this->createRequest(ReturnRequest::TYPE_EXCHANGE, ReturnRequest::STATUS_APPROVED);

        $this->from(route('admin.returns.show', $exchangeId))
            ->put(route('admin.returns.update', $exchangeId), [
                'trang_thai' => ReturnRequest::STATUS_REFUNDED,
                'phan_hoi' => 'Đã hoàn tiền.',
            ])
            ->assertRedirect(route('admin.returns.show', $exchangeId))
            ->assertSessionHasErrors('trang_thai');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $exchangeId,
            'trang_thai' => ReturnRequest::STATUS_APPROVED,
        ]);

        $this->put(route('admin.returns.update', $exchangeId), [
            'trang_thai' => ReturnRequest::STATUS_COMPLETED,
            'phan_hoi' => 'Đã đổi sản phẩm thành công.',
        ])->assertRedirect(route('admin.returns.show', $exchangeId));

        $this->get(route('admin.returns.show', $exchangeId))
            ->assertOk()
            ->assertSee('Chờ nhận hàng hoàn');
    }

    public function test_approval_requires_customer_agreement_and_policy_review(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_PROCESSING);

        $this->from(route('admin.returns.show', $requestId))
            ->put(route('admin.returns.update', $requestId), [
                'trang_thai' => ReturnRequest::STATUS_APPROVED,
                'phan_hoi' => 'Duyệt yêu cầu.',
                'customer_agreed' => 0,
                'policy_reviewed' => 0,
            ])
            ->assertRedirect(route('admin.returns.show', $requestId))
            ->assertSessionHasErrors('customer_agreed');

        $this->from(route('admin.returns.show', $requestId))
            ->put(route('admin.returns.update', $requestId), [
                'trang_thai' => ReturnRequest::STATUS_APPROVED,
                'phan_hoi' => 'Duyệt yêu cầu.',
                'customer_agreed' => 1,
                'policy_reviewed' => 0,
            ])
            ->assertRedirect(route('admin.returns.show', $requestId))
            ->assertSessionHasErrors('policy_reviewed');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_PROCESSING,
        ]);
        $this->assertDatabaseCount('support_return_histories', 0);
    }

    public function test_staff_can_create_a_linked_return_request_from_a_chat_conversation(): void
    {
        $this->seedOrderDetails();
        $conversation = $this->createConversation();

        $this->post(route('admin.support-chat.return-requests.store', $conversation), [
            'customer_id' => 300,
            'order_id' => 100,
            'chi_tiet_don_hang_id' => 200,
            'loai_yeu_cau' => ReturnRequest::TYPE_RETURN,
            'so_luong' => 1,
            'ly_do' => 'Khách báo sản phẩm bị lỗi.',
        ])->assertRedirect(route('admin.support-chat.index', [
            'status' => 'open',
            'conversation_id' => $conversation->id,
        ]))
            ->assertSessionHas('success');

        $request = DB::table('yeu_cau_doi_tras')->first();
        $this->assertDatabaseHas('support_return_workflows', [
            'return_request_id' => $request->id,
            'conversation_id' => $conversation->id,
        ]);
        $this->assertDatabaseHas('support_return_histories', [
            'return_request_id' => $request->id,
            'from_status' => null,
            'to_status' => ReturnRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conversation->id,
            'sender_type' => 'staff',
        ]);

        $this->withSession(['support_chat_token' => 'return-customer-token'])
            ->getJson(route('support-chat.index'))
            ->assertOk()
            ->assertJsonPath('return_requests.0.id', $request->id)
            ->assertJsonPath('return_requests.0.status', 'Chờ tiếp nhận');
    }

    public function test_staff_cannot_create_a_return_for_another_customers_order_or_over_order_quantity(): void
    {
        $this->seedOrderDetails();
        DB::table('tai_khoans')->insert([
            'id' => 301,
            'ho_ten' => 'Khách hàng khác',
            'email' => 'other@example.test',
        ]);
        $conversation = $this->createConversation();
        $payload = [
            'customer_id' => 301,
            'order_id' => 100,
            'chi_tiet_don_hang_id' => 200,
            'loai_yeu_cau' => ReturnRequest::TYPE_RETURN,
            'so_luong' => 1,
            'ly_do' => 'Khách báo sản phẩm bị lỗi.',
        ];

        $this->from(route('admin.support-chat.index'))
            ->post(route('admin.support-chat.return-requests.store', $conversation), $payload)
            ->assertSessionHasErrors('order_id');

        $payload['customer_id'] = 300;
        $payload['so_luong'] = 3;
        $this->from(route('admin.support-chat.index'))
            ->post(route('admin.support-chat.return-requests.store', $conversation), $payload)
            ->assertSessionHasErrors('so_luong');

        $this->assertDatabaseCount('yeu_cau_doi_tras', 0);
    }

    public function test_order_item_picker_limits_available_quantity_and_hides_other_customers_orders(): void
    {
        $this->seedOrderDetails();
        $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_PROCESSING);

        $this->getJson(route('admin.support-chat.orders.items', [
            'order' => 100,
            'customer_id' => 300,
        ]))
            ->assertOk()
            ->assertJsonPath('items.0.id', 200)
            ->assertJsonPath('items.0.available_quantity', 1);

        DB::table('tai_khoans')->insert([
            'id' => 301,
            'ho_ten' => 'Khách hàng khác',
            'email' => 'other@example.test',
        ]);
        $this->getJson(route('admin.support-chat.orders.items', [
            'order' => 100,
            'customer_id' => 301,
        ]))->assertNotFound();
    }

    public function test_return_cannot_be_marked_refunded_before_financial_controls_are_available(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_INSPECTED);

        $this->from(route('admin.returns.show', $requestId))
            ->put(route('admin.returns.update', $requestId), [
                'trang_thai' => ReturnRequest::STATUS_REFUNDED,
                'refund_amount' => 250001,
                'refund_method' => 'bank_transfer',
            ])
            ->assertRedirect(route('admin.returns.show', $requestId))
            ->assertSessionHasErrors('trang_thai');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_INSPECTED,
        ]);
        $this->assertDatabaseCount('support_return_histories', 0);
    }

    public function test_refund_wallet_shows_only_reconciled_data_and_read_only_return_records(): void
    {
        $this->seedOrderDetails();
        $pendingRefundId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_INSPECTED);
        $recordedRefundId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_COMPLETED);
        ReturnRequestWorkflow::query()->create([
            'return_request_id' => $pendingRefundId,
        ]);
        ReturnRequestWorkflow::query()->create([
            'return_request_id' => $recordedRefundId,
            'refund_amount' => 250000,
            'refund_method' => 'bank_transfer',
            'refund_reference' => 'LEGACY-REFUND-100',
            'refunded_at' => now()->subDays(2),
        ]);

        $this->get(route('admin.finance.refunds.index'))
            ->assertOk()
            ->assertSee('Ví hoàn tiền AOVU')
            ->assertSee('Chưa kết nối')
            ->assertSee('Chưa có dữ liệu số dư được đối soát')
            ->assertSee('#DT-'.str_pad((string) $pendingRefundId, 6, '0', STR_PAD_LEFT))
            ->assertSee('#DT-'.str_pad((string) $recordedRefundId, 6, '0', STR_PAD_LEFT))
            ->assertSee('ORD-100')
            ->assertSee('LEGACY-REFUND-100')
            ->assertSee('250.000 ₫')
            ->assertDontSee('20.000.000 ₫')
            ->assertDontSee('name="amount"')
            ->assertDontSee('name="account_number"');
    }

    public function test_exchange_requires_stock_price_difference_settlement_and_delivery_confirmation(): void
    {
        $this->seedOrderDetails();
        DB::table('san_phams')->insert([
            'id' => 501,
            'ma_san_pham' => 'REPLACEMENT-501',
            'ten_san_pham' => 'Áo thay thế',
        ]);
        DB::table('bien_thes')->insert([
            'id' => 601,
            'san_pham_id' => 501,
            'sku' => 'REPLACEMENT-M',
            'gia_bien_the' => 300000,
            'so_luong_ton' => 3,
            'trang_thai' => 1,
        ]);
        $requestId = $this->createRequest(ReturnRequest::TYPE_EXCHANGE, ReturnRequest::STATUS_PROCESSING);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_APPROVED,
            'customer_agreed' => 1,
            'policy_reviewed' => 1,
            'policy_notes' => 'Đổi đúng thời hạn.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_AWAITING_RETURN,
        ])->assertRedirect(route('admin.returns.show', $requestId));
        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_RETURN_RECEIVED,
        ])->assertRedirect(route('admin.returns.show', $requestId));
        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_INSPECTED,
            'inspection_notes' => 'Sản phẩm đủ điều kiện đổi.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $payload = [
            'trang_thai' => ReturnRequest::STATUS_EXCHANGE_SHIPPED,
            'replacement_variant_id' => 601,
            'tracking_code' => 'SHIP-601',
        ];
        $this->from(route('admin.returns.show', $requestId))
            ->put(route('admin.returns.update', $requestId), $payload)
            ->assertSessionHasErrors('price_difference_settled');

        $payload['price_difference_settled'] = 1;
        $payload['price_difference_note'] = 'Khách đã thanh toán chênh lệch.';
        $this->put(route('admin.returns.update', $requestId), $payload)
            ->assertRedirect(route('admin.returns.show', $requestId));

        $this->assertDatabaseHas('support_return_workflows', [
            'return_request_id' => $requestId,
            'replacement_variant_id' => 601,
            'replacement_price' => 300000,
            'price_difference' => 50000,
            'tracking_code' => 'SHIP-601',
        ]);
        $this->assertDatabaseHas('bien_thes', ['id' => 601, 'so_luong_ton' => 2]);

        $this->from(route('admin.returns.show', $requestId))
            ->put(route('admin.returns.update', $requestId), [
                'trang_thai' => ReturnRequest::STATUS_COMPLETED,
            ])
            ->assertSessionHasErrors('replacement_delivered');

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_COMPLETED,
            'replacement_delivered' => 1,
            'phan_hoi' => 'Khách đã nhận hàng đổi.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->assertNotNull(DB::table('support_return_workflows')
            ->where('return_request_id', $requestId)
            ->value('delivered_at'));
    }

    private function seedOrderDetails(): void
    {
        DB::table('don_hangs')->insert([
            'id' => 100,
            'ma_don_hang' => 'ORD-100',
            'ten_nguoi_nhan' => 'Khách hàng thử nghiệm',
            'tong_tien' => 500000,
            'tai_khoan_id' => 300,
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            'id' => 200,
            'don_hang_id' => 100,
            'ten_san_pham' => 'Áo Linen',
            'don_gia' => 250000,
            'thanh_tien' => 500000,
            'so_luong' => 2,
        ]);
        DB::table('tai_khoans')->insert([
            'id' => 300,
            'ho_ten' => 'Khách hàng thử nghiệm',
            'email' => 'customer@example.test',
        ]);
    }

    private function createRequest(int $type, int $status, string $reason = 'Sản phẩm cần đổi hoặc trả'): int
    {
        return DB::table('yeu_cau_doi_tras')->insertGetId([
            'don_hang_id' => 100,
            'chi_tiet_don_hang_id' => 200,
            'tai_khoan_id' => 300,
            'loai_yeu_cau' => $type,
            'so_luong' => 1,
            'ly_do' => $reason,
            'ngay_tao' => now(),
            'trang_thai' => $status,
        ]);
    }

    private function createConversation(): SupportConversation
    {
        return SupportConversation::factory()->create([
            'visitor_token_hash' => hash('sha256', 'return-customer-token'),
            'customer_name' => 'Khách hàng thử nghiệm',
            'customer_email' => 'customer@example.test',
        ]);
    }
}
