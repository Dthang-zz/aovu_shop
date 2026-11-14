<?php

namespace Tests\Feature\Admin;

use App\Models\SupportConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
