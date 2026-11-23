<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ConversationEvent> */
class ConversationEventFactory extends Factory
{
    protected $model = ConversationEvent::class;

    public function definition(): array
    {
        $conversation = Conversation::factory()->create();

        return ['conversation_id' => $conversation->id, 'user_id' => $conversation->poster_id, 'event' => 'closed', 'created_at' => now()];
    }
}
