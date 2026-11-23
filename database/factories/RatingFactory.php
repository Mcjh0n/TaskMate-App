<?php

namespace Database\Factories;

use App\Enums\RatingDirection;
use App\Models\Agreement;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rating> */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'agreement_id' => Agreement::factory(),
            'task_id' => fn (array $attributes) => Agreement::find($attributes['agreement_id'])?->task_id,
            'rater_id' => fn (array $attributes) => Agreement::find($attributes['agreement_id'])?->poster_id,
            'ratee_id' => fn (array $attributes) => Agreement::find($attributes['agreement_id'])?->student_id,
            'direction' => RatingDirection::PosterToStudent,
            'overall_experience' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Rating $rating): void {
            $agreement = Agreement::find($rating->agreement_id);
            if (! $agreement) {
                return;
            }
            $rating->task_id = $agreement->task_id;
            $rating->rater_id = $agreement->poster_id;
            $rating->ratee_id = $agreement->student_id;
            $rating->direction = RatingDirection::PosterToStudent;
        })->afterCreating(function (Rating $rating): void {
            foreach (array_keys(config('taskmate.ratings.dimensions.'.$rating->direction->value, [])) as $dimension) {
                $rating->scores()->create(['dimension' => $dimension, 'score' => fake()->numberBetween(1, 5)]);
            }
        });
    }
}
