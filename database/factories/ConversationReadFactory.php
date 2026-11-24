<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationRead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

/** @extends Factory<ConversationRead> */
class ConversationReadFactory extends Factory
{
    protected $model = ConversationRead::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => function (array $attributes): int {
                $conversation = $attributes['conversation_id'];

                if ($conversation instanceof Conversation) {
                    return $conversation->poster_id;
                }

                if ($conversation instanceof Collection) {
                    $first = $conversation->first();

                    if ($first instanceof Conversation) {
                        return $first->poster_id;
                    }
                }

                if (is_int($conversation) || is_string($conversation)) {
                    return Conversation::query()->findOrFail($conversation)->poster_id;
                }

                throw new LogicException('The conversation factory did not provide a conversation.');
            },
            'last_read_message_id' => null,
        ];
    }
}
