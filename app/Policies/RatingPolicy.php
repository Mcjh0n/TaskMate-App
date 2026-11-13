<?php

namespace App\Policies;

use App\Models\Rating;
use App\Models\User;

class RatingPolicy
{
    public function view(User $user, Rating $rating): bool
    {
        return $user->isAdmin() || $rating->rater_id === $user->id || $rating->ratee_id === $user->id;
    }

    public function moderate(User $user, Rating $rating): bool
    {
        return $user->isAdmin() && ! in_array($user->id, [$rating->rater_id, $rating->ratee_id], true);
    }
}
