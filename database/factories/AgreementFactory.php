<?php

namespace Database\Factories;

use App\Enums\AgreementStatus;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Agreement> */
class AgreementFactory extends Factory
{
    protected $model = Agreement::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory()->assigned(),
            'application_id' => Application::factory()->accepted(),
            'poster_id' => User::factory()->verified()->facultyStaff(),
            'student_id' => User::factory()->verified(),
            'status' => AgreementStatus::Negotiating,
            'current_version_id' => null,
            'locked_at' => null,
        ];
    }
}
