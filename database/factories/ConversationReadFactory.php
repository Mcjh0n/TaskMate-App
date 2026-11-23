<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationRead;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ConversationRead> */
class ConversationReadFactory extends Factory
{
    protected $model = ConversationRead::class;

    public function definition(): array
    {
        $conversation = Conversation::factory()->create();

        return ['conversation_id' => $conversation->id, 'user_id' => $conversation->poster_id, 'last_read_message_id' => null];
    }
}
