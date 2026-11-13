<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminTaskClosureRequest;
use App\Models\AdminAction;
use App\Models\Agreement;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminTaskClosureController extends Controller
{
    public function __invoke(AdminTaskClosureRequest $request, Task $task): RedirectResponse
    {
        DB::transaction(function () use ($request, $task): void {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $agreement = Agreement::query()->lockForUpdate()->where('task_id', $task->id)->firstOrFail();
            abort_unless($task->status === TaskStatus::InProgress && $agreement->status === AgreementStatus::Agreed, 403);
            $note = $request->validated('note');
            $agreement->status = AgreementStatus::Cancelled;
            $agreement->cancelled_at = now();
            $agreement->cancelled_by = $request->user()->id;
            $agreement->cancellation_reason = $note;
            $agreement->save();
            $task->status = TaskStatus::Cancelled;
            $task->save();
            $task->applications()->where('status', ApplicationStatus::Accepted->value)->update(['status' => ApplicationStatus::Cancelled->value, 'responded_at' => now(), 'updated_at' => now()]);
            foreach ([$task, $agreement] as $subject) {
                $entry = new AdminAction(['admin_id' => $request->user()->id, 'action' => 'in_progress_task_closed', 'note' => $note, 'metadata' => ['task_id' => $task->id, 'agreement_id' => $agreement->id]]);
                $subject->adminActions()->save($entry);
            }
        });
        return back()->with('status', 'The task was closed as cancelled by an administrator.');
    }
}
