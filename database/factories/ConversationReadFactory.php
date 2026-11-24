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
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => fn (array $attributes) => Conversation::query()->findOrFail($attributes['conversation_id'])->poster_id,
            'last_read_message_id' => null,
        ];
    }
}
