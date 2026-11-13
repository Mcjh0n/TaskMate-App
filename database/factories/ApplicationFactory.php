<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Application> */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory()->approved(),
            'applicant_id' => User::factory()->verified(),
            'message' => fake()->paragraph(),
            'proposed_amount' => fake()->optional()->randomFloat(2, 500, 5000),
            'status' => ApplicationStatus::Pending,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => ApplicationStatus::Accepted, 'responded_at' => now()]);
    }
}
