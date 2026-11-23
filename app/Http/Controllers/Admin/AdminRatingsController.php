<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateRatingRequest;
use App\Models\AdminAction;
use App\Models\Rating;
use App\Services\RatingVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminRatingsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'direction' => ['nullable', Rule::in(['poster_to_student', 'student_to_poster'])],
            'hidden' => ['nullable', Rule::in(['0', '1'])],
            'rating_min' => ['nullable', 'integer', 'between:1,5'],
            'rating_max' => ['nullable', 'integer', 'between:1,5'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $ratings = Rating::query()->with(['rater:id,name', 'ratee:id,name', 'task:id,title'])
            ->when($filters['direction'] ?? null, fn ($q, $v) => $q->where('direction', $v))
            ->when(array_key_exists('hidden', $filters), fn ($q) => $q->where('is_hidden', $filters['hidden'] === '1'))
            ->when($filters['rating_min'] ?? null, fn ($q, $v) => $q->where('overall_experience', '>=', $v))
            ->when($filters['rating_max'] ?? null, fn ($q, $v) => $q->where('overall_experience', '<=', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($users) => $users->whereHas('rater', fn ($u) => $u->where('name', 'like', "%{$v}%"))->orWhereHas('ratee', fn ($u) => $u->where('name', 'like', "%{$v}%"))))
            ->latest()->paginate(20)->withQueryString()->through(fn (Rating $rating) => [
                'id' => $rating->id,
                'taskTitle' => $rating->task->title,
                'raterName' => $rating->rater->name,
                'rateeName' => $rating->ratee->name,
                'direction' => $rating->direction->value,
                'overall' => $rating->overall_experience,
                'hidden' => $rating->is_hidden,
                'date' => $rating->created_at?->toDateTimeString(),
            ]);
        return Inertia::render('admin/ratings/index', ['ratings' => $ratings, 'filters' => $filters]);
    }

    public function show(Rating $rating, RatingVisibility $visibility): Response
    {
        $rating->load(['scores', 'agreement.currentVersion', 'task:id,title', 'rater:id,name', 'ratee:id,name']);
        return Inertia::render('admin/ratings/show', [
            'rating' => [
                'id' => $rating->id,
                'overall' => $rating->overall_experience,
                'comment' => $rating->comment,
                'direction' => $rating->direction->value,
                'scores' => $rating->scores->map(fn ($score) => ['dimension' => $score->dimension, 'label' => config('taskmate.ratings.dimensions.'.$rating->direction->value.'.'.$score->dimension, $score->dimension), 'score' => $score->score])->values(),
                'taskTitle' => $rating->task->title,
                'agreementId' => $rating->agreement_id,
                'recordReference' => $rating->agreement->currentVersion?->content_hash,
                'agreedAmount' => $rating->agreement->currentVersion?->final_amount,
                'paymentArrangement' => $rating->agreement->currentVersion?->payment_arrangement,
                'rater' => $rating->rater->name,
                'ratee' => $rating->ratee->name,
                'hidden' => $rating->is_hidden,
                'hiddenReason' => $rating->hidden_reason,
                'visibleToOthers' => $visibility->isVisibleToOthers($rating),
            ],
            'notice' => 'Rating visibility, moderation, and appeals rules are provisional and have not been finalized by the team.',
        ]);
    }

    public function moderate(ModerateRatingRequest $request, Rating $rating): RedirectResponse
    {
        DB::transaction(function () use ($request, $rating): void {
            $rating = Rating::query()->lockForUpdate()->findOrFail($rating->id);
            Gate::authorize('moderate', $rating);
            $action = $request->validated('action');
            if ($action === 'hide') {
                $rating->is_hidden = true;
                $rating->hidden_by = $request->user()->id;
                $rating->hidden_at = now();
                $rating->hidden_reason = $request->validated('reason');
            } else {
                $rating->is_hidden = false;
                $rating->hidden_by = null;
                $rating->hidden_at = null;
                $rating->hidden_reason = null;
            }
            $rating->save();
            $rating->adminActions()->create([
                'admin_id' => $request->user()->id,
                'action' => $action === 'hide' ? 'rating_hidden' : 'rating_restored',
                'note' => $action === 'hide' ? $request->validated('reason') : null,
            ]);
        });
        return back()->with('status', 'The rating moderation change was recorded.');
    }
}
