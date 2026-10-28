<?php

namespace Tests\Feature;

use App\Models\SupportConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_start_a_conversation_and_poll_new_messages(): void
    {
        $this->withSession(['support_chat_token' => 'customer-session-token']);

        $response = $this->postJson(route('support-chat.store'), [
            'name' => 'Nguyễn Minh Anh',
            'email' => 'minhanh@example.test',
            'message' => 'Tôi muốn hỏi về đơn hàng.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('conversation.status', SupportConversation::STATUS_OPEN)
            ->assertJsonPath('messages.0.body', 'Tôi muốn hỏi về đơn hàng.');

        $conversation = SupportConversation::query()->firstOrFail();
        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
            'visitor_token_hash' => hash('sha256', 'customer-session-token'),
            'customer_name' => 'Nguyễn Minh Anh',
            'customer_email' => 'minhanh@example.test',
            'status' => SupportConversation::STATUS_OPEN,
        ]);

        $this->getJson(route('support-chat.index', ['after_id' => 0]))
            ->assertOk()
            ->assertJsonPath('conversation.id', $conversation->id)
            ->assertJsonPath('messages.0.body', 'Tôi muốn hỏi về đơn hàng.');
    }

    public function test_customer_cannot_read_another_session_conversation(): void
    {
        $conversation = SupportConversation::factory()->create([
            'visitor_token_hash' => hash('sha256', 'first-customer-token'),
        ]);
        $conversation->messages()->create([
            'sender_type' => 'customer',
            'body' => 'Thông tin riêng của khách thứ nhất.',
        ]);
        $this->withSession(['support_chat_token' => 'different-customer-token']);

        $this->getJson(route('support-chat.index'))
            ->assertOk()
            ->assertJsonPath('conversation', null)
            ->assertJsonCount(0, 'messages');
    }

    public function test_customer_must_provide_a_name_to_start_chat(): void
    {
        $this->postJson(route('support-chat.store'), [
            'message' => 'Tôi cần hỗ trợ.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('name')
            ->assertJsonPath('errors.name.0', 'Vui lòng nhập tên của bạn để bắt đầu trò chuyện.');

        $this->assertDatabaseCount('support_conversations', 0);
        $this->assertDatabaseCount('support_messages', 0);
    }

    public function test_customer_must_provide_a_nonempty_message(): void
    {
        $this->postJson(route('support-chat.store'), [
            'name' => 'Nguyễn Minh Anh',
            'message' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('message')
            ->assertJsonPath('errors.message.0', 'Vui lòng nhập nội dung tin nhắn.');

        $this->assertDatabaseCount('support_conversations', 0);
        $this->assertDatabaseCount('support_messages', 0);
    }
}
