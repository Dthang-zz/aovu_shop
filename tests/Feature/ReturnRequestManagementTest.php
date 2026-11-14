<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ProductVariant;
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
            $table->dateTime('ngay_dat')->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
            $table->dateTime('ngay_hoan_thanh')->nullable();
            $table->decimal('tien_giam_voucher', 12, 0)->default(0);
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->unsignedInteger('bien_the_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
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

    public function test_staff_cannot_create_a_return_request_for_an_order_that_is_not_completed(): void
    {
        $this->seedOrderDetails();
        DB::table('don_hangs')
            ->where('id', 100)
            ->update(['trang_thai' => Order::STATUS_SHIPPING]);
        $conversation = $this->createConversation();
        $payload = [
            'customer_id' => 300,
            'order_id' => 100,
            'chi_tiet_don_hang_id' => 200,
            'loai_yeu_cau' => ReturnRequest::TYPE_RETURN,
            'so_luong' => 1,
            'ly_do' => 'Khách báo sản phẩm bị lỗi.',
        ];

        $this->post(route('admin.returns.store'), $payload)
            ->assertSessionHasErrors([
                'order_id' => 'Chỉ được tạo yêu cầu đổi/trả cho đơn hàng đã hoàn thành.',
            ]);
        $this->post(route('admin.support-chat.return-requests.store', $conversation), $payload)
            ->assertSessionHasErrors([
                'order_id' => 'Chỉ được tạo yêu cầu đổi/trả cho đơn hàng đã hoàn thành.',
            ]);

        $this->assertDatabaseCount('yeu_cau_doi_tras', 0);
        $this->assertDatabaseCount('support_return_workflows', 0);
        $this->assertDatabaseCount('support_return_histories', 0);
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

    public function test_return_order_picker_only_lists_completed_orders_and_rejects_items_from_other_statuses(): void
    {
        $this->seedOrderDetails();
        DB::table('don_hangs')->insert([
            'id' => 101,
            'ma_don_hang' => 'ORD-101',
            'tai_khoan_id' => 300,
            'ngay_dat' => now(),
            'trang_thai' => Order::STATUS_SHIPPING,
        ]);

        $this->getJson(route('admin.support-chat.customers.orders', 300))
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', 100);
        $this->getJson(route('admin.support-chat.orders.items', [
            'order' => 101,
            'customer_id' => 300,
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

    public function test_finance_can_confirm_a_manual_transfer_for_an_inspected_return(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_INSPECTED);

        $this->post('/admin/finance/refunds/'.$requestId.'/confirm-manual-payment', [
            'refund_amount' => 250000,
            'refund_reference' => 'MB-20261103-001',
            'confirmed' => 1,
        ])
            ->assertRedirect(route('admin.finance.refunds.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_REFUNDED,
        ]);
        $this->assertDatabaseHas('support_return_workflows', [
            'return_request_id' => $requestId,
            'refund_amount' => 250000,
            'refund_method' => 'manual_bank_transfer',
            'refund_reference' => 'MB-20261103-001',
        ]);
        $this->assertDatabaseHas('support_return_histories', [
            'return_request_id' => $requestId,
            'from_status' => ReturnRequest::STATUS_INSPECTED,
            'to_status' => ReturnRequest::STATUS_REFUNDED,
        ]);
    }

    public function test_refund_wallet_shows_manual_confirmation_controls_and_refund_history(): void
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
            ->assertDontSee('Số dư khả dụng')
            ->assertDontSee('Sổ giao dịch nguồn tiền')
            ->assertDontSee('Phương thức hoàn tiền')
            ->assertSee('#DT-'.str_pad((string) $pendingRefundId, 6, '0', STR_PAD_LEFT))
            ->assertSee('#DT-'.str_pad((string) $recordedRefundId, 6, '0', STR_PAD_LEFT))
            ->assertSee('ORD-100')
            ->assertSee('LEGACY-REFUND-100')
            ->assertSee('250.000 ₫')
            ->assertDontSee('20.000.000 ₫')
            ->assertSee('Xác nhận đã chuyển')
            ->assertSee('name="refund_amount"', false)
            ->assertDontSee('name="account_number"');
    }

    public function test_finance_cannot_confirm_manual_payment_before_a_return_is_inspected(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_PROCESSING);

        $this->from(route('admin.finance.refunds.index'))
            ->post(route('admin.finance.refunds.confirm-manual-payment', $requestId), [
                'refund_amount' => 250000,
                'confirmed' => 1,
            ])
            ->assertRedirect(route('admin.finance.refunds.index'))
            ->assertSessionHasErrors('refund');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_PROCESSING,
        ]);
        $this->assertDatabaseCount('support_return_workflows', 0);
    }

    public function test_exchange_requires_stock_price_difference_settlement_and_delivery_confirmation(): void
    {
        $this->seedOrderDetails();
        $replacement = $this->createVariant('REPLACEMENT-M', 300000, 3);
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
            'replacement_variant_id' => $replacement->id,
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
            'replacement_variant_id' => $replacement->id,
            'replacement_name' => 'Áo thay thế · REPLACEMENT-M (Đen / M)',
            'replacement_price' => 300000,
            'price_difference' => 50000,
            'tracking_code' => 'SHIP-601',
        ]);
        $this->assertSame(2, $replacement->fresh()->stock);

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

    public function test_return_requests_are_only_allowed_within_seven_days_of_order_completion(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $this->seedOrderDetails();
        DB::table('don_hangs')->where('id', 100)->update(['ngay_hoan_thanh' => '2026-11-03 09:00:00']);
        DB::table('don_hangs')->insert([
            'id' => 101,
            'ma_don_hang' => 'ORD-101',
            'tai_khoan_id' => 300,
            'ngay_dat' => '2026-10-28 09:00:00',
            'ngay_hoan_thanh' => '2026-11-02 09:00:00',
            'trang_thai' => Order::STATUS_COMPLETED,
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            'id' => 201,
            'don_hang_id' => 101,
            'ten_san_pham' => 'Quần Kaki',
            'don_gia' => 300000,
            'thanh_tien' => 300000,
            'so_luong' => 1,
        ]);

        $this->from(route('admin.returns.create'))
            ->post(route('admin.returns.store'), [
                'customer_id' => 300,
                'order_id' => 101,
                'chi_tiet_don_hang_id' => 201,
                'loai_yeu_cau' => ReturnRequest::TYPE_RETURN,
                'so_luong' => 1,
                'ly_do' => 'Khách đổi ý.',
            ])
            ->assertSessionHasErrors(['order_id' => 'Đơn hàng đã quá hạn đổi/trả 7 ngày.']);

        $this->assertDatabaseCount('yeu_cau_doi_tras', 0);
        $this->getJson(route('admin.support-chat.customers.orders', 300))
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', 100);
        $this->getJson(route('admin.support-chat.orders.items', ['order' => 101, 'customer_id' => 300]))
            ->assertNotFound();
    }

    public function test_passing_inspection_returns_the_items_to_the_original_variant_stock(): void
    {
        $this->seedOrderDetails();
        $original = $this->createVariant('LINEN-M', 250000, 4);
        DB::table('chi_tiet_don_hangs')->where('id', 200)->update(['product_variant_id' => $original->id]);
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_RETURN_RECEIVED);
        ReturnRequestWorkflow::query()->create(['return_request_id' => $requestId]);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_INSPECTED,
            'inspection_notes' => 'Hàng còn nguyên tem mác.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->assertSame(5, $original->fresh()->stock);
        $this->assertNotNull(ReturnRequestWorkflow::query()->where('return_request_id', $requestId)->value('restocked_at'));
        $this->assertDatabaseHas('support_return_histories', [
            'return_request_id' => $requestId,
            'to_status' => ReturnRequest::STATUS_INSPECTED,
            'note' => 'Hàng còn nguyên tem mác.',
        ]);
    }

    public function test_inspection_without_collecting_the_item_does_not_change_stock(): void
    {
        $this->seedOrderDetails();
        $original = $this->createVariant('LINEN-M', 250000, 4);
        DB::table('chi_tiet_don_hangs')->where('id', 200)->update(['product_variant_id' => $original->id]);
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_APPROVED);
        ReturnRequestWorkflow::query()->create(['return_request_id' => $requestId, 'collection_waived' => true]);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_INSPECTED,
            'inspection_notes' => 'Khách gửi ảnh lỗi, không cần thu hồi.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->assertSame(4, $original->fresh()->stock);
        $this->assertNull(ReturnRequestWorkflow::query()->where('return_request_id', $requestId)->value('restocked_at'));
    }

    public function test_failed_inspection_rejects_a_received_return_without_restocking(): void
    {
        $this->seedOrderDetails();
        $original = $this->createVariant('LINEN-M', 250000, 4);
        DB::table('chi_tiet_don_hangs')->where('id', 200)->update(['product_variant_id' => $original->id]);
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_RETURN_RECEIVED);

        $this->from(route('admin.returns.show', $requestId))
            ->put(route('admin.returns.update', $requestId), [
                'trang_thai' => ReturnRequest::STATUS_REJECTED,
                'phan_hoi' => 'Sản phẩm đã qua sử dụng.',
            ])
            ->assertSessionHasErrors(['inspection_notes' => 'Vui lòng ghi nhận kết quả kiểm tra sản phẩm.']);

        $this->assertDatabaseHas('yeu_cau_doi_tras', ['id' => $requestId, 'trang_thai' => ReturnRequest::STATUS_RETURN_RECEIVED]);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_REJECTED,
            'phan_hoi' => 'Sản phẩm đã qua sử dụng.',
            'inspection_notes' => 'Áo có vết bẩn, đã giặt.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->assertDatabaseHas('yeu_cau_doi_tras', ['id' => $requestId, 'trang_thai' => ReturnRequest::STATUS_REJECTED]);
        $this->assertSame(4, $original->fresh()->stock);
    }

    public function test_finance_cannot_refund_more_than_the_customer_paid_for_the_returned_items(): void
    {
        $this->seedOrderDetails();
        DB::table('don_hangs')->where('id', 100)->update(['tien_giam_voucher' => 50000]);
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_INSPECTED);

        $this->get(route('admin.finance.refunds.index'))
            ->assertOk()
            ->assertSee('225.000 ₫')
            ->assertSee('max="225000"', false);

        $this->from(route('admin.finance.refunds.index'))
            ->post(route('admin.finance.refunds.confirm-manual-payment', $requestId), [
                'refund_amount' => 225001,
                'confirmed' => 1,
            ])
            ->assertSessionHasErrors(['refund_amount' => 'Số tiền hoàn vượt quá giá trị hàng trả (225.000 ₫).']);

        $this->assertDatabaseHas('yeu_cau_doi_tras', ['id' => $requestId, 'trang_thai' => ReturnRequest::STATUS_INSPECTED]);
        $this->assertDatabaseMissing('support_return_workflows', ['return_request_id' => $requestId, 'refund_amount' => 225001]);

        $this->post(route('admin.finance.refunds.confirm-manual-payment', $requestId), [
            'refund_amount' => 225000,
            'confirmed' => 1,
        ])->assertRedirect(route('admin.finance.refunds.index'));

        $this->assertDatabaseHas('yeu_cau_doi_tras', ['id' => $requestId, 'trang_thai' => ReturnRequest::STATUS_REFUNDED]);
        $this->assertDatabaseHas('support_return_workflows', ['return_request_id' => $requestId, 'refund_amount' => 225000]);
    }

    public function test_refund_wallet_lists_returns_still_in_progress_with_the_missing_step(): void
    {
        $this->seedOrderDetails();
        $approvedReturnId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_APPROVED);
        $inspectedReturnId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_INSPECTED);
        $exchangeId = $this->createRequest(ReturnRequest::TYPE_EXCHANGE, ReturnRequest::STATUS_APPROVED);

        $response = $this->get(route('admin.finance.refunds.index'));

        $response->assertOk()
            ->assertViewHas('inProgressCount', 1)
            ->assertViewHas('pendingCount', 1)
            ->assertSee('Phiếu trả hàng đang xử lý')
            ->assertSee('Chờ khách gửi hàng hoặc miễn thu hồi để kiểm tra')
            ->assertSee('Xử lý phiếu');
        $this->assertSame([$approvedReturnId], $response->viewData('inProgressRefunds')->pluck('id')->all());
        $this->assertSame([$inspectedReturnId], $response->viewData('pendingRefunds')->pluck('id')->all());
        $this->assertNotContains($exchangeId, $response->viewData('inProgressRefunds')->pluck('id')->all());
    }

    public function test_refund_wallet_shows_success_message_after_confirming_a_transfer(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_INSPECTED);

        $this->followingRedirects()
            ->post(route('admin.finance.refunds.confirm-manual-payment', $requestId), [
                'refund_amount' => 250000,
                'confirmed' => 1,
            ])
            ->assertOk()
            ->assertSee('Đã xác nhận khách hàng được hoàn tiền bằng chuyển khoản thủ công.');
    }

    public function test_return_detail_page_is_read_only_and_links_to_the_edit_page(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_PENDING, 'Áo bị sờn chỉ');

        $this->get(route('admin.returns.show', $requestId))
            ->assertOk()
            ->assertSee('Áo bị sờn chỉ')
            ->assertSee('Chờ tiếp nhận')
            ->assertDontSee('name="trang_thai"', false)
            ->assertDontSee(route('admin.returns.update', $requestId).'"', false)
            ->assertSee(route('admin.returns.edit', $requestId));
    }

    public function test_return_edit_page_offers_the_allowed_next_statuses(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_PENDING, 'Áo bị sờn chỉ');

        $this->get(route('admin.returns.edit', $requestId))
            ->assertOk()
            ->assertSee('Áo bị sờn chỉ')
            ->assertSee(route('admin.returns.update', $requestId))
            ->assertSee('<option value="'.ReturnRequest::STATUS_PROCESSING.'"', false)
            ->assertSee('<option value="'.ReturnRequest::STATUS_REJECTED.'"', false)
            ->assertDontSee('<option value="'.ReturnRequest::STATUS_APPROVED.'"', false)
            ->assertDontSee('name="replacement_variant_id"', false);
    }

    public function test_exchange_edit_page_lists_replacement_variants_in_stock(): void
    {
        $this->seedOrderDetails();
        $inStock = $this->createVariant('SWAP-L', 260000, 2);
        $this->createVariant('SWAP-XL', 260000, 0);
        $requestId = $this->createRequest(ReturnRequest::TYPE_EXCHANGE, ReturnRequest::STATUS_INSPECTED);

        $this->get(route('admin.returns.edit', $requestId))
            ->assertOk()
            ->assertSee('<option value="'.$inStock->id.'"', false)
            ->assertSee('SWAP-L')
            ->assertDontSee('SWAP-XL');
    }

    public function test_closed_return_has_no_status_form_on_detail_or_edit_page(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_REJECTED);

        $this->get(route('admin.returns.show', $requestId))
            ->assertOk()
            ->assertDontSee(route('admin.returns.edit', $requestId))
            ->assertSee('Yêu cầu đã kết thúc, không thể chuyển trạng thái thêm.');
        $this->get(route('admin.returns.edit', $requestId))
            ->assertOk()
            ->assertDontSee('name="trang_thai"', false)
            ->assertSee('Yêu cầu đã kết thúc, không thể chuyển trạng thái thêm.');
    }

    public function test_return_edit_page_returns_not_found_for_missing_request(): void
    {
        $this->get(route('admin.returns.edit', 999))->assertNotFound();
    }

    private function createVariant(string $sku, int $price, int $stock): ProductVariant
    {
        $productId = DB::table('san_phams')->insertGetId([
            'ma_san_pham' => 'SP-'.$sku,
            'ten_san_pham' => 'Áo thay thế',
        ]);

        return ProductVariant::query()->create([
            'product_id' => $productId,
            'sku' => $sku,
            'color' => 'Đen',
            'size' => 'M',
            'price' => $price,
            'stock' => $stock,
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);
    }

    private function seedOrderDetails(): void
    {
        DB::table('don_hangs')->insert([
            'id' => 100,
            'ma_don_hang' => 'ORD-100',
            'ten_nguoi_nhan' => 'Khách hàng thử nghiệm',
            'tong_tien' => 500000,
            'tai_khoan_id' => 300,
            'ngay_dat' => now(),
            'trang_thai' => Order::STATUS_COMPLETED,
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
