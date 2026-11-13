<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\RatingDirection;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\Rating;
use App\Models\RatingScore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RatingWorkflow
{
    public function submit(Agreement $agreement, User $rater, array $data): Rating
    {
        return DB::transaction(function () use ($agreement, $rater, $data): Rating {
            $agreement = Agreement::query()->lockForUpdate()->findOrFail($agreement->id);
            Gate::authorize('rate', $agreement);
            $poster = $agreement->poster_id === $rater->id;
            $direction = $poster ? RatingDirection::PosterToStudent : RatingDirection::StudentToPoster;
            $rating = new Rating;
            $rating->agreement_id = $agreement->id;
            $rating->task_id = $agreement->task_id;
            $rating->rater_id = $rater->id;
            $rating->ratee_id = $poster ? $agreement->student_id : $agreement->poster_id;
            $rating->direction = $direction;
            $rating->overall_experience = $data['overall_experience'];
            $rating->comment = $data['comment'] ?? null;
            $rating->save();
            foreach ($data['scores'] as $dimension => $score) {
                $rating->scores()->create(['dimension' => $dimension, 'score' => $score]);
            }
            return $rating;
        });
    }

    public function pendingFor(User $user): array
    {
        $visibility = app(RatingVisibility::class);
        return Agreement::query()->with(['task.completion', 'currentVersion', 'paymentEvents'])
            ->where('status', AgreementStatus::Agreed->value)
            ->whereHas('task', fn ($q) => $q->where('status', TaskStatus::Completed->value))
            ->where(fn ($q) => $q->where('poster_id', $user->id)->orWhere('student_id', $user->id))
            ->whereDoesntHave('ratings', fn ($q) => $q->where('rater_id', $user->id))
            ->get()->filter(fn (Agreement $agreement) => ! $visibility->windowClosed($agreement))
            ->map(fn (Agreement $agreement) => [
                'id' => $agreement->id,
                'taskTitle' => $agreement->task->title,
                'daysLeft' => max(0, now()->startOfDay()->diffInDays($agreement->task->completion->completed_at->copy()->addDays((int) config('taskmate.ratings.window_days', 30))->startOfDay(), false)),
            ])->values()->all();
    }
}
