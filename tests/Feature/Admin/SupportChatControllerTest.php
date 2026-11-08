<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\SupportConversation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupportChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_reply_to_and_close_a_conversation(): void
    {
        $conversation = SupportConversation::factory()->create([
            'customer_name' => 'Nguyễn Minh Anh',
            'customer_email' => 'minhanh@example.test',
        ]);
        $conversation->messages()->create([
            'sender_type' => 'customer',
            'body' => 'Đơn hàng của tôi đang ở đâu?',
        ]);

        $this->get(route('admin.support-chat.index'))
            ->assertOk()
            ->assertSee('CSKH trực tuyến')
            ->assertSee('Nguyễn Minh Anh');

        $this->getJson(route('admin.support-chat.messages', $conversation))
            ->assertOk()
            ->assertJsonPath('conversation.customer_email', 'minhanh@example.test')
            ->assertJsonPath('messages.0.body', 'Đơn hàng của tôi đang ở đâu?');

        $this->postJson(route('admin.support-chat.messages.store', $conversation), [
            'message' => 'Chúng tôi đang kiểm tra đơn hàng của bạn.',
        ])->assertCreated()
            ->assertJsonPath('messages.0.sender_type', 'staff');

        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conversation->id,
            'sender_type' => 'staff',
            'sender_name' => 'CSKH',
            'body' => 'Chúng tôi đang kiểm tra đơn hàng của bạn.',
        ]);

        $this->patch(route('admin.support-chat.status.update', $conversation), [
            'status' => SupportConversation::STATUS_CLOSED,
        ])->assertRedirect(route('admin.support-chat.index', ['status' => 'closed']));

        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
            'status' => SupportConversation::STATUS_CLOSED,
        ]);
    }

    public function test_conversation_lists_the_customers_orders_with_links_to_the_order_page(): void
    {
        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->nullable();
        });
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang');
            $table->unsignedInteger('tai_khoan_id')->nullable();
            $table->string('email_nguoi_nhan')->nullable();
            $table->dateTime('ngay_dat')->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
        });
        $accountId = DB::table('tai_khoans')->insertGetId(['email' => 'minhanh@example.test']);
        $accountOrderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'ORD-ACCOUNT', 'tai_khoan_id' => $accountId, 'ngay_dat' => '2026-11-05 10:00:00',
            'tong_tien' => 350000, 'trang_thai' => Order::STATUS_COMPLETED,
        ]);
        DB::table('don_hangs')->insert([
            ['ma_don_hang' => 'ORD-GUEST-EMAIL', 'tai_khoan_id' => null, 'email_nguoi_nhan' => 'MinhAnh@Example.test', 'ngay_dat' => '2026-11-01 10:00:00', 'tong_tien' => 100000, 'trang_thai' => Order::STATUS_PENDING],
            ['ma_don_hang' => 'ORD-OTHER', 'tai_khoan_id' => 99, 'email_nguoi_nhan' => 'other@example.test', 'ngay_dat' => '2026-11-06 10:00:00', 'tong_tien' => 100000, 'trang_thai' => Order::STATUS_PENDING],
        ]);
        $conversation = SupportConversation::factory()->create(['customer_email' => 'minhanh@example.test']);

        $this->getJson(route('admin.support-chat.messages', $conversation))
            ->assertOk()
            ->assertJsonCount(2, 'customer_orders')
            ->assertJsonPath('customer_orders.0.code', 'ORD-ACCOUNT')
            ->assertJsonPath('customer_orders.0.status', 'Hoàn thành')
            ->assertJsonPath('customer_orders.0.url', route('admin.orders.show', $accountOrderId))
            ->assertJsonPath('customer_orders.1.code', 'ORD-GUEST-EMAIL')
            ->assertJsonMissingPath('return_requests');
    }

    public function test_staff_cannot_reply_to_a_closed_conversation(): void
    {
        $conversation = SupportConversation::factory()->create([
            'status' => SupportConversation::STATUS_CLOSED,
        ]);

        $this->postJson(route('admin.support-chat.messages.store', $conversation), [
            'message' => 'Phản hồi không hợp lệ.',
        ])->assertConflict();

        $this->assertDatabaseCount('support_messages', 0);
    }

    public function test_staff_reply_rejects_an_empty_message(): void
    {
        $conversation = SupportConversation::factory()->create();

        $this->postJson(route('admin.support-chat.messages.store', $conversation), [
            'message' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        $this->assertDatabaseCount('support_messages', 0);
    }

    public function test_admin_inbox_escapes_customer_supplied_html(): void
    {
        $maliciousText = '<script>alert("xss")</script>';
        $conversation = SupportConversation::factory()->create([
            'customer_name' => $maliciousText,
        ]);
        $conversation->messages()->create([
            'sender_type' => 'customer',
            'body' => $maliciousText,
        ]);

        $this->get(route('admin.support-chat.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee($maliciousText, false);
    }
}
