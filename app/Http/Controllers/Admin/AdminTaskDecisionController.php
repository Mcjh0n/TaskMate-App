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
            $decision = $request->string('decision')->toString();
            $decisions = [
                'approve' => ['status' => TaskStatus::Approved, 'action' => 'task_approved', 'event' => 'task.approved'],
                'reject' => ['status' => TaskStatus::Rejected, 'action' => 'task_rejected', 'event' => 'task.rejected'],
                'request_changes' => ['status' => TaskStatus::ChangesRequested, 'action' => 'task_changes_requested', 'event' => 'task.changes_requested'],
            ];
            $decisionData = $decisions[$decision] ?? throw new \LogicException('Unsupported task review decision.');
            $task->status = $decisionData['status'];
            $task->reviewed_by = $request->user()->id;
            $task->reviewed_at = now()->toMutable();
            $task->decision_source = 'admin';
            $task->review_note = $decision === 'approve' ? null : $request->validated('note');
            $task->save();

            $task->adminActions()->create([
                'admin_id' => $request->user()->id,
                'action' => $decisionData['action'],
                'note' => $request->validated('note'),
                'metadata' => ['decision' => $decision],
            ]);
            $alerts->send(User::query()->findOrFail($task->poster_id), $decisionData['event'], route('tasks.show', $task->id, false), ['task_id' => $task->id], ['task_title' => $task->title], $request->user()->id);
        });

        return back()->with('status', 'The task review decision was saved.');
    }
}
