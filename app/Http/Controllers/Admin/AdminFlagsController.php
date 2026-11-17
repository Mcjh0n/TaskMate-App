<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentFlagStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminFlagDecisionRequest;
use App\Models\AdminAction;
use App\Models\ContentFlag;
use App\Models\ConversationMessage;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminFlagsController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = array_keys(config('taskmate.offplatform.category_labels', []));
        $filters = $request->validate(['category' => ['nullable', Rule::in($categories)], 'status' => ['nullable', Rule::in(array_column(ContentFlagStatus::cases(), 'value'))]]);
        $flags = ContentFlag::query()->with(['sourceUser:id,name,user_type', 'flaggable'])
            ->when($filters['category'] ?? null, fn ($q, $value) => $q->where('category', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->orderByRaw("CASE status WHEN 'open' THEN 0 ELSE 1 END")->orderBy('created_at')
            ->paginate(20)->withQueryString()->through(fn (ContentFlag $flag) => [
                'id' => $flag->id, 'category' => $flag->category, 'categoryLabel' => config('taskmate.offplatform.category_labels.'.$flag->category, $flag->category),
                'field' => $flag->field, 'author' => $flag->sourceUser->name, 'status' => $flag->status->value, 'createdAt' => $flag->created_at?->toDateTimeString(),
                'context' => class_basename($flag->flaggable_type).' #'.$flag->flaggable_id,
            ]);
        return Inertia::render('admin/flags/index', ['flags' => $flags, 'filters' => $filters, 'categories' => collect(config('taskmate.offplatform.category_labels', []))->map(fn ($label, $value) => compact('value', 'label'))->values()]);
    }

    public function show(ContentFlag $flag): Response
    {
        $flag->load(['sourceUser.sanctions.issuer:id,name', 'flaggable']);
        $subject = $flag->flaggable;
        $field = match ($flag->field) {
            'message' => $subject->message ?? '',
            'terms' => $subject->terms ?? '',
            'comment' => $subject->comment ?? '',
            'body' => $subject->body ?? '',
            default => $subject->{$flag->field} ?? '',
        };
        $isMessage = $subject instanceof ConversationMessage;
        if ($isMessage) $subject->load(['sender:id,name,user_type,organization_name', 'conversation.task:id,title']);
        $relatedReport = $isMessage ? Report::query()->where('subject_type', $subject->getMorphClass())->where('subject_id', $subject->id)->first() : null;
        return Inertia::render('admin/flags/show', [
            'flag' => [
                'id' => $flag->id, 'category' => $flag->category, 'categoryLabel' => config('taskmate.offplatform.category_labels.'.$flag->category, $flag->category),
                'matched' => $flag->matched, 'field' => $flag->field, 'content' => $field, 'status' => $flag->status->value, 'note' => $flag->review_note, 'createdAt' => $flag->created_at?->toDateTimeString(),
                'author' => ['id' => $flag->sourceUser->id, 'name' => $isMessage ? \App\Models\Conversation::displayName($subject->sender) : ($flag->sourceUser->isOrganization() ? $flag->sourceUser->organization_name : $flag->sourceUser->name)],
                'task' => $isMessage ? ['id' => $subject->conversation->task->id, 'title' => $subject->conversation->task->title] : null,
                'relatedReportUrl' => $relatedReport ? route('admin.reports.show', $relatedReport) : null,
                'sanctions' => $flag->sourceUser->sanctions->map(fn ($s) => ['type' => $s->type->value, 'reason' => $s->reason, 'active' => ! $s->lifted_at && ($s->ends_at === null || $s->ends_at->isFuture())])->values(),
            ],
        ]);
    }

    public function decision(AdminFlagDecisionRequest $request, ContentFlag $flag): RedirectResponse
    {
        DB::transaction(function () use ($request, $flag): void {
            $flag = ContentFlag::query()->lockForUpdate()->findOrFail($flag->id);
            $decision = $request->validated('decision');
            $flag->status = ContentFlagStatus::from($decision);
            $flag->reviewed_by = $request->user()->id;
            $flag->reviewed_at = now();
            $flag->review_note = $request->validated('note');
            $flag->save();
            $entry = new AdminAction(['admin_id' => $request->user()->id, 'action' => 'content_flag_'.$decision, 'note' => $request->validated('note'), 'metadata' => ['category' => $flag->category]]);
            $flag->adminActions()->save($entry);
        });
        return back()->with('status', 'The content flag review was recorded.');
    }
}
