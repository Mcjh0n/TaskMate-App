<?php

namespace Database\Factories;

use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\User;
use App\Services\AgreementContentHasher;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgreementVersion> */
class AgreementVersionFactory extends Factory
{
    protected $model = AgreementVersion::class;

    public function configure(): static
    {
        return $this->afterMaking(function (AgreementVersion $version): void {
            $version->content_hash = app(AgreementContentHasher::class)->hash($version);
        });
    }

    public function definition(): array
    {
        $values = [
            'deliverables' => fake()->sentence(),
            'terms' => fake()->paragraph(),
            'final_amount' => fake()->randomFloat(2, 100, 10000),
            'start_date' => today()->addDay()->toDateString(),
            'due_date' => today()->addWeek()->toDateString(),
            'duration_days' => fake()->numberBetween(1, 14),
            'work_mode' => 'on_campus',
            'workplace_name' => 'Synthetic campus location',
            'workplace_address' => 'Synthetic campus address',
            'payment_arrangement' => 'Synthetic direct payment arrangement after both parties agree.',
        ];

        return [
            'agreement_id' => Agreement::factory(),
            'version_number' => 1,
            'proposed_by' => User::factory()->verified(),
            ...$values,
            'comment' => null,
            'content_hash' => hash('sha256', json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'created_at' => now(),
        ];
    }
}
