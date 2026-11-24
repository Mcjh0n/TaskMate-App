<?php

namespace Database\Factories;

use App\Enums\ContentFlagStatus;
use App\Models\ContentFlag;
use App\Models\ConversationMessage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContentFlag> */
class ContentFlagFactory extends Factory
{
    protected $model = ContentFlag::class;

    public function definition(): array
    {
        return [
            'flaggable_type' => Task::class,
            'flaggable_id' => Task::factory(),
            'source_user_id' => User::factory()->verified()->facultyStaff(),
            'category' => 'bypass_agreement',
            'matched' => ['Skip the agreement'],
            'field' => 'description',
            'status' => ContentFlagStatus::Open,
            'created_at' => now(),
        ];
    }

    public function forMessage(ConversationMessage $message): static
    {
        return $this->for($message, 'flaggable')->state([
            'source_user_id' => $message->sender_id,
            'field' => 'body',
        ]);
    }
}
