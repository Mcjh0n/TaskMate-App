<?php

namespace App\Services;

use App\Models\User;

class RatingSummary
{
    public function forUser(User $user): array
    {
        $ratings = app(RatingVisibility::class)->visibleQuery()->where('ratee_id', $user->id)->with('scores')->get();
        $minimum = (int) config('taskmate.ratings.min_count_for_average', 3);
        $summary = ['count' => $ratings->count(), 'average' => null, 'dimensions' => []];
        if ($ratings->count() >= $minimum) {
            $summary['average'] = round($ratings->avg('overall_experience'), 1);
            foreach (config('taskmate.ratings.dimensions', []) as $dimensions) {
                foreach ($dimensions as $key => $label) {
                    $scores = $ratings->flatMap(fn ($rating) => $rating->scores->where('dimension', $key)->pluck('score'));
                    if ($scores->isNotEmpty()) $summary['dimensions'][$key] = ['label' => $label, 'average' => round($scores->avg(), 1)];
                }
            }
        }
        return $summary;
    }
}
