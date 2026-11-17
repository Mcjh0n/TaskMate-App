<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ConversationMessage> */
class ConversationMessageFactory extends Factory
{
    protected $model = ConversationMessage::class;

    public function definition(): array
    {
        $conversation = Conversation::factory()->create();

        return [
            'conversation_id' => $conversation->id,
            'sender_id' => fake()->randomElement([$conversation->poster_id, $conversation->applicant_id]),
            'body' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
