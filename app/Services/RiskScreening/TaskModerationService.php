<?php

namespace App\Services\RiskScreening;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class TaskModerationService
{
    public function __construct(private readonly RiskScreeningService $screening) {}

    public function screenAndApply(Task $task): RiskAssessment
    {
        return DB::transaction(function () use ($task): RiskAssessment {
            $task->loadMissing('poster');
            $assessment = $this->screening->screen($task);

            $task->risk_level = $assessment->level->value;
            $task->risk_score = $assessment->score;
            $task->screened_at = now();
            $task->decision_source = null;

            if ($assessment->outcome === 'auto_approved') {
                $task->status = TaskStatus::Approved;
                $task->decision_source = 'system';
                $task->review_note = null;
                $task->reviewed_by = null;
                $task->reviewed_at = null;
            } elseif ($assessment->outcome === 'blocked') {
                $task->status = TaskStatus::Rejected;
                $task->decision_source = 'system';
                $task->review_note = 'This task could not be approved under the current platform rules. Please revise it or contact an administrator.';
                $task->reviewed_by = null;
                $task->reviewed_at = now();
            } else {
                $task->status = TaskStatus::PendingReview;
            }

            $task->save();
            $screening = new \App\Models\TaskScreening();
            $screening->task()->associate($task);
            $screening->risk_level = $assessment->level->value;
            $screening->risk_score = $assessment->score;
            $screening->factors = $assessment->factors;
            $screening->keyword_hits = $assessment->keywordHits;
            $screening->hard_triggers = $assessment->hardTriggers;
            $screening->outcome = $assessment->outcome;
            $screening->save();

            return $assessment;
        });
    }

    public function posterMessage(RiskAssessment $assessment): string
    {
        return match ($assessment->outcome) {
            'auto_approved' => 'Your task was approved.',
            'blocked' => 'Your task could not be approved.',
            default => 'Your task is awaiting review.',
        };
    }
}
