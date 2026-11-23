<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminTaskDecisionRequest;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskMateNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminTaskDecisionController extends Controller
{
    public function __invoke(AdminTaskDecisionRequest $request, Task $task, TaskMateNotifications $alerts): RedirectResponse
    {
        DB::transaction(function () use ($request, $task, $alerts): void {
            $decision = $request->validated('decision');
            $task->status = match ($decision) {
                'approve' => TaskStatus::Approved,
                'reject' => TaskStatus::Rejected,
                'request_changes' => TaskStatus::ChangesRequested,
            };
            $task->reviewed_by = $request->user()->id;
            $task->reviewed_at = now();
            $task->decision_source = 'admin';
            $task->review_note = $decision === 'approve' ? null : $request->validated('note');
            $task->save();

            $task->adminActions()->create([
                'admin_id' => $request->user()->id,
                'action' => match ($decision) {
                    'approve' => 'task_approved',
                    'reject' => 'task_rejected',
                    'request_changes' => 'task_changes_requested',
                },
                'note' => $request->validated('note'),
                'metadata' => ['decision' => $decision],
            ]);
            $event = match ($decision) {
                'approve' => 'task.approved',
                'reject' => 'task.rejected',
                'request_changes' => 'task.changes_requested',
            };
            $alerts->send(User::query()->findOrFail($task->poster_id), $event, route('tasks.show', $task->id, false), ['task_id' => $task->id], ['task_title' => $task->title], $request->user()->id);
        });

        return back()->with('status', 'The task review decision was saved.');
    }
}
