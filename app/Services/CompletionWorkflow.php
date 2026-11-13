<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompletionWorkflow
{
    public function markDelivered(Agreement $agreement, User $student, ?string $note): TaskCompletion
    {
        return DB::transaction(function () use ($agreement, $student, $note): TaskCompletion {
            $agreement = Agreement::query()->lockForUpdate()->findOrFail($agreement->id);
            $task = Task::query()->lockForUpdate()->findOrFail($agreement->task_id);
            $this->assertLockedInProgress($agreement, $task);
            if ($student->id !== $agreement->student_id) {
                abort(403);
            }

            $completion = $agreement->completion()->lockForUpdate()->first();
            if ($completion?->delivered_at) {
                throw ValidationException::withMessages(['completion' => 'Work has already been marked as delivered.']);
            }
            $completion ??= new TaskCompletion;
            $completion->agreement_id = $agreement->id;
            $completion->delivered_at = now();
            $completion->delivered_note = $note;
            $completion->save();
            return $completion;
        });
    }

    public function recordCompletion(Agreement $agreement, User $poster, ?string $note): TaskCompletion
    {
        return DB::transaction(function () use ($agreement, $poster, $note): TaskCompletion {
            $agreement = Agreement::query()->lockForUpdate()->findOrFail($agreement->id);
            $task = Task::query()->lockForUpdate()->findOrFail($agreement->task_id);
            $this->assertLockedInProgress($agreement, $task);
            if ($poster->id !== $agreement->poster_id) {
                abort(403);
            }

            $completion = $agreement->completion()->lockForUpdate()->first();
            if ($completion?->completed_at) {
                throw ValidationException::withMessages(['completion' => 'This task has already been recorded as completed.']);
            }
            $completion ??= new TaskCompletion;
            $completion->agreement_id = $agreement->id;
            $completion->completed_at = now();
            $completion->completed_by = $poster->id;
            $completion->completion_note = $note;
            $completion->save();
            $task->status = TaskStatus::Completed;
            $task->save();
            return $completion;
        });
    }

    private function assertLockedInProgress(Agreement $agreement, Task $task): void
    {
        if ($agreement->status !== AgreementStatus::Agreed || ! $agreement->locked_at || $task->status !== TaskStatus::InProgress) {
            throw ValidationException::withMessages(['completion' => 'Completion can only be recorded while the locked agreement is in progress.']);
        }
    }
}
