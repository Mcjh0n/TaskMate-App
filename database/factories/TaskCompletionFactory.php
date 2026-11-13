<?php

namespace Database\Factories;

use App\Models\Agreement;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskCompletion> */
class TaskCompletionFactory extends Factory
{
    protected $model = TaskCompletion::class;
    public function definition(): array
    {
        return [
            'agreement_id' => Agreement::factory(),
            'delivered_at' => now(),
            'delivered_note' => 'Synthetic delivery note.',
            'completed_at' => now(),
            'completed_by' => User::factory()->verified()->facultyStaff(),
            'completion_note' => 'Synthetic completion note.',
        ];
    }
}
