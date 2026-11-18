<?php

namespace Database\Factories;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_conversation_id' => SupportConversation::factory(),
            'sender_type' => SupportMessage::SENDER_CUSTOMER,
            'sender_name' => null,
            'body' => fake()->sentence(),
        ];
    }
}
