<?php

namespace App\Http\Controllers;

use App\Enums\RatingDirection;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Agreement;
use App\Services\RatingWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RatingController extends Controller
{
    public function create(Agreement $agreement, Request $request): Response
    {
        Gate::authorize('rate', $agreement);
        $direction = $agreement->poster_id === $request->user()->id ? RatingDirection::PosterToStudent : RatingDirection::StudentToPoster;
        $ratee = $direction === RatingDirection::PosterToStudent ? $agreement->student : $agreement->poster;

        return Inertia::render('ratings/create', [
            'agreement' => ['id' => $agreement->id, 'taskTitle' => $agreement->task->title],
            'rateeName' => $ratee->isOrganization() ? $ratee->organization_name : $ratee->name,
            'commentMaxLength' => config('taskmate.ratings.comment_max_length', 1000),
            'visibility' => config('taskmate.ratings.visibility', 'after_both'),
            'reportUrl' => route('agreements.show', $agreement),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(StoreRatingRequest $request, Agreement $agreement, RatingWorkflow $workflow): RedirectResponse
    {
        $workflow->submit($agreement, $request->user(), $request->validated());
        $next = config('taskmate.ratings.visibility', 'after_both') === 'immediate'
            ? 'Your rating is now visible to the other person.'
            : 'Your rating stays hidden from the other person until both of you have rated or the '.(int) config('taskmate.ratings.window_days', 30).'-day rating window closes.';

        return redirect()->route('agreements.show', $agreement)->with('status', 'Thank you for sharing your feedback. '.$next);
    }
}
