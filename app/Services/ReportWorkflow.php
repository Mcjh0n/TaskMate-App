<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\PaymentEventType;
use App\Enums\PaymentStatus;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Application;
use App\Models\Task;
use App\Models\Agreement;
use App\Models\PaymentEvent;
use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\ReportStatement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReportWorkflow
{
    /** @param array<int, UploadedFile> $files */
    public function createProblem(User $reporter, User $reportedUser, ?Task $task, ?Agreement $agreement, ReportType $type, string $description, array $files): Report
    {
        if ($type === ReportType::NonPayment || $reporter->id === $reportedUser->id || $reportedUser->isAdmin()) {
            throw ValidationException::withMessages(['report' => 'This report cannot be submitted.']);
        }
        if ($type === ReportType::IncompleteWork && (! $agreement || $agreement->status !== AgreementStatus::Agreed || ! $agreement->locked_at)) {
            throw ValidationException::withMessages(['report' => 'Incomplete work reports require a locked agreement.']);
        }
        $storedPaths = [];
        try {
            return DB::transaction(function () use ($reporter, $reportedUser, $task, $agreement, $type, $description, $files, &$storedPaths): Report {
                User::query()->lockForUpdate()->findOrFail($reporter->id);
                $limit = (int) config('taskmate.reports.rate_limit_per_day', 5);
                if ($reporter->filedReports()->whereDate('created_at', today())->count() >= $limit) {
                    throw ValidationException::withMessages(['report' => 'You have reached the daily report limit. Try again tomorrow.']);
                }
                $duplicate = $reporter->filedReports()->where('reported_user_id', $reportedUser->id)
                    ->where('task_id', $task?->id)->where('type', $type->value)
                    ->whereIn('status', [ReportStatus::Open->value, ReportStatus::UnderReview->value])->exists();
                if ($duplicate) throw ValidationException::withMessages(['report' => 'You already have an open report of this type about this person and task.']);
                $report = new Report;
                $report->reporter_id = $reporter->id;
                $report->reported_user_id = $reportedUser->id;
                $report->task_id = $task?->id;
                $report->agreement_id = $agreement?->id;
                $report->type = $type;
                $report->priority = config('taskmate.reports.types.'.$type->value.'.priority', 'normal');
                $report->description = $description;
                $report->status = ReportStatus::Open;
                $report->save();
                $this->storeEvidence($report, $reporter, $files, $storedPaths);
                return $report;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) Storage::disk('evidence')->delete($path);
            throw $exception;
        }
    }

    public function canReportNonPayment(Agreement $agreement): bool
    {
        if ($agreement->status !== AgreementStatus::Agreed || ! $agreement->locked_at
            || $agreement->paymentStatus() === PaymentStatus::ConfirmedReceived || $agreement->paymentIsSettled()) {
            return false;
        }

        $latest = $agreement->paymentEvents()->reorder()->latest('id')->first();
        $hasPriorPaidEvent = $agreement->paymentEvents()->where('event', PaymentEventType::MarkedPaid->value)->exists();
        $studentReportedNotReceived = $latest?->event === PaymentEventType::ReportedNotReceived
            && $hasPriorPaidEvent
            && $latest->actor_id === $agreement->student_id;
        if ($studentReportedNotReceived) return true;

        if ($hasPriorPaidEvent) return false;

        $waitDays = (int) config('taskmate.reports.non_payment_wait_days', 3);
        $completionDate = $agreement->completion?->completed_at;
        $waitAfterCompletion = $completionDate && now()->greaterThanOrEqualTo($completionDate->copy()->addDays($waitDays));
        $dueDate = $agreement->currentVersion?->due_date;
        $waitAfterDueDate = $dueDate && today()->greaterThanOrEqualTo($dueDate->copy()->addDays($waitDays));

        return (bool) ($waitAfterCompletion || $waitAfterDueDate);
    }

    /** @param array<int, UploadedFile> $files */
    public function createNonPayment(Agreement $agreement, User $student, string $description, array $files): Report
    {
        $storedPaths = [];
        try {
            return DB::transaction(function () use ($agreement, $student, $description, $files, &$storedPaths): Report {
                $agreement = Agreement::query()->lockForUpdate()->findOrFail($agreement->id);
                if ($student->id !== $agreement->student_id || ! $this->canReportNonPayment($agreement)) {
                    throw ValidationException::withMessages(['report' => 'You cannot report non-payment yet.']);
                }

                if ($agreement->reports()->whereIn('status', [ReportStatus::Open->value, ReportStatus::UnderReview->value])->exists()) {
                    throw ValidationException::withMessages(['report' => 'An open non-payment report already exists for this agreement.']);
                }

                $report = new Report;
                $report->reporter_id = $student->id;
                $report->reported_user_id = $agreement->poster_id;
                $report->task_id = $agreement->task_id;
                $report->agreement_id = $agreement->id;
                $report->type = ReportType::NonPayment;
                $report->priority = config('taskmate.reports.types.non_payment.priority', 'high');
                $report->description = $description;
                $report->status = ReportStatus::Open;
                $report->save();

                $latest = $agreement->paymentEvents()->reorder()->latest('id')->first();
                if ($latest?->event !== PaymentEventType::ReportedNotReceived) {
                    $event = new PaymentEvent;
                    $event->agreement_id = $agreement->id;
                    $event->actor_id = $student->id;
                    $event->event = PaymentEventType::ReportedNotReceived;
                    $event->created_at = now();
                    $event->save();
                }

                $this->storeEvidence($report, $student, $files, $storedPaths);

                return $report;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) Storage::disk('evidence')->delete($path);
            throw $exception;
        }
    }

    /** @param array<int, UploadedFile> $files */
    public function addRespondentStatement(Report $report, User $poster, string $body, array $files): ReportStatement
    {
        $storedPaths = [];
        try {
            return DB::transaction(function () use ($report, $poster, $body, $files, &$storedPaths): ReportStatement {
                $report = Report::query()->lockForUpdate()->findOrFail($report->id);
                if (! $poster->can('reply', $report) && $report->reporter_id !== $poster->id) abort(403);
                if (! in_array($report->status, [ReportStatus::Open, ReportStatus::UnderReview], true)) abort(403);
                if ($report->evidence()->count() + count($files) > (int) config('taskmate.reports.evidence_max_files', 5)) {
                    throw ValidationException::withMessages(['evidence' => 'The evidence file limit for this report has been reached.']);
                }
                $statement = new ReportStatement;
                $statement->report_id = $report->id;
                $statement->user_id = $poster->id;
                $statement->role = $report->reporter_id === $poster->id ? 'reporter' : 'respondent';
                $statement->body = $body;
                $statement->created_at = now();
                $statement->save();
                $this->storeEvidence($report, $poster, $files, $storedPaths);
                return $statement;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) Storage::disk('evidence')->delete($path);
            throw $exception;
        }
    }

    /** @param array<int, UploadedFile> $files @param array<int, string> $storedPaths */
    public function storeEvidence(Report $report, User $uploader, array $files, array &$storedPaths): void
    {
        foreach ($files as $file) {
            $path = Storage::disk('evidence')->putFile('reports/'.$report->id, $file);
            if (! $path) throw new \RuntimeException('Evidence could not be stored.');
            $storedPaths[] = $path;
            $evidence = new ReportEvidence;
            $evidence->report_id = $report->id;
            $evidence->uploaded_by = $uploader->id;
            $evidence->original_name = mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', basename($file->getClientOriginalName())) ?? 'evidence', 0, 255);
            $evidence->stored_path = $path;
            $evidence->mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file->getRealPath()) ?: 'application/octet-stream';
            $evidence->size_bytes = $file->getSize() ?: 0;
            $evidence->created_at = now();
            $evidence->save();
        }
    }
}
