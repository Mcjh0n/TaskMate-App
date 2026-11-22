<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;

class TaskSubmissionWorkflow
{
    public function __construct(private readonly TaskMateNotifications $alerts) {}

    /** Put every new or resubmitted task in the administrator review queue. */
    public function submitForReview(Task $task): void
    {
        $task->status = TaskStatus::PendingReview;
        $task->risk_level = null;
        $task->risk_score = null;
        $task->screened_at = null;
        $task->decision_source = null;
        $task->review_note = null;
        $task->reviewed_by = null;
        $task->reviewed_at = null;
        $task->save();

        $this->alerts->admins(
            'task.awaiting_review',
            route('admin.tasks.show', $task->id, false),
            ['task_id' => $task->id],
            ['task_title' => $task->title],
            $task->poster_id,
        );
    }
}
