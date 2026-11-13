<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReportEvidence> */
class ReportEvidenceFactory extends Factory
{
    protected $model = ReportEvidence::class;
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'uploaded_by' => User::factory()->verified(),
            'original_name' => 'synthetic-evidence.pdf',
            'stored_path' => 'reports/synthetic/'.fake()->uuid().'.pdf',
            'mime' => 'application/pdf',
            'size_bytes' => 1024,
            'created_at' => now(),
        ];
    }
}
