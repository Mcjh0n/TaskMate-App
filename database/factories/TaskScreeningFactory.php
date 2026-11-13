<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskScreening;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskScreening> */
class TaskScreeningFactory extends Factory
{
    protected $model = TaskScreening::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'risk_level' => 'low',
            'risk_score' => 0,
            'factors' => [['key' => 'category_base', 'label' => 'Task category', 'points' => 0, 'reason' => 'Synthetic test factor.']],
            'keyword_hits' => [],
            'hard_triggers' => [],
            'outcome' => 'needs_review',
        ];
    }
}
