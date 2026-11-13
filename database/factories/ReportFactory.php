<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Agreement;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Report> */
class ReportFactory extends Factory
{
    protected $model = Report::class;
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory()->verified(),
            'reported_user_id' => User::factory()->verified()->facultyStaff(),
            'task_id' => Task::factory()->assigned(),
            'agreement_id' => Agreement::factory(),
            'type' => ReportType::NonPayment,
            'description' => 'Synthetic description for a non-payment report.',
            'status' => ReportStatus::Open,
        ];
    }
}
