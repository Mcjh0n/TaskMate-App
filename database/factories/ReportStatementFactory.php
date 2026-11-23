<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportStatement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReportStatement> */
class ReportStatementFactory extends Factory
{
    protected $model = ReportStatement::class;

    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'user_id' => User::factory()->verified()->facultyStaff(),
            'role' => 'respondent',
            'body' => 'Synthetic respondent statement.',
            'created_at' => now(),
        ];
    }
}
