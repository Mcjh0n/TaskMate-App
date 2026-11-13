<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminTaskDecisionRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminTaskDecisionController extends Controller
{
    public function __invoke(AdminTaskDecisionRequest $request, Task $task): RedirectResponse
    {
        DB::transaction(function () use ($request, $task): void {
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
                'metadata' => ['decision' => $decision, 'risk_level' => $task->risk_level],
            ]);
        });

        return back()->with('status', 'The task review decision was saved.');
    }
}
