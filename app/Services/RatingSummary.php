<?php

namespace App\Services;

use App\Models\User;

class RatingSummary
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $ratings = app(RatingVisibility::class)->visibleQuery()->where('ratee_id', $user->id)->get(['overall_experience']);
        $minimum = (int) config('taskmate.ratings.min_count_for_average', 3);
        $summary = ['count' => $ratings->count(), 'average' => null];
        if ($ratings->count() >= $minimum) {
            $summary['average'] = round($ratings->avg('overall_experience'), 1);
        }

        return $summary;
    }
}
