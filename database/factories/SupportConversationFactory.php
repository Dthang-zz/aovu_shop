<?php

namespace Database\Factories;

use App\Models\SupportConversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportConversation>
 */
class SupportConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_token_hash' => hash('sha256', Str::random(64)),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'status' => SupportConversation::STATUS_OPEN,
        ];
    }
}
