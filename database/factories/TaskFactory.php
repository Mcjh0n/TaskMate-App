<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'poster_id' => User::factory()->verified()->facultyStaff(),
            'title' => fake()->sentence(5),
            'category' => array_key_first(config('taskmate.categories')),
            'description' => fake()->paragraph(),
            'required_activities' => fake()->paragraph(),
            'deliverables' => fake()->sentence(),
            'estimated_duration_hours' => fake()->randomFloat(1, 1, 40),
            'skill_requirements' => fake()->optional()->sentence(),
            'complexity' => 'medium',
            'budget' => fake()->randomFloat(2, 500, 10000),
            'work_mode' => 'onsite',
            'workplace_name' => 'Synthetic Campus Office',
            'workplace_address' => 'Synthetic campus location',
            'deadline' => fake()->optional()->dateTimeBetween('+2 days', '+2 months')?->format('Y-m-d'),
            'status' => TaskStatus::PendingReview,
            'guidelines_confirmed_at' => now(),
        ];
    }
}
