<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Conversation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conversation> */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        $task = Task::factory()->approved()->create(['work_mode' => 'online', 'workplace_name' => null, 'workplace_address' => null, 'workplace_setting' => null]);
        $applicant = User::factory()->verified()->create();
        $application = Application::factory()->for($task)->for($applicant, 'applicant')->create();

        return [
            'application_id' => $application->id,
            'task_id' => $task->id,
            'poster_id' => $task->poster_id,
            'applicant_id' => $applicant->id,
        ];
    }
}
