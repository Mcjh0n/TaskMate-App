<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\AgreementConfirmation;
use App\Models\AgreementMessage;
use App\Models\AgreementVersion;
use App\Models\Application;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgreementWorkflow
{
    public function __construct(private readonly AgreementContentHasher $hasher, private readonly OffPlatformScanner $scanner) {}

    public function create(Task $task, User $poster, array $data): Agreement
    {
        return DB::transaction(function () use ($task, $poster, $data): Agreement {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $accepted = $task->applications()->where('status', ApplicationStatus::Accepted->value)->lockForUpdate()->get();
            if ($task->status !== TaskStatus::Assigned || $accepted->count() !== 1 || $task->agreement()->exists()) {
                throw ValidationException::withMessages(['agreement' => 'This task is not ready for an agreement.']);
            }
            if (in_array($task->work_mode, ['onsite', 'hybrid'], true)
                && (! $task->workplace_name || ! $task->workplace_address)) {
                throw ValidationException::withMessages(['task' => 'The task must have complete workplace details before an agreement can be proposed.']);
            }

            $application = $accepted->first();
            $agreement = new Agreement;
            $agreement->task_id = $task->id;
            $agreement->application_id = $application->id;
            $agreement->poster_id = $poster->id;
            $agreement->student_id = $application->applicant_id;
            $agreement->status = AgreementStatus::Negotiating;
            $agreement->save();

            $this->addVersion($agreement, $poster, $task, $data, null, 1);

            return $agreement;
        });
    }

    public function revise(Agreement $agreement, User $user, array $data): AgreementVersion
    {
        return DB::transaction(function () use ($agreement, $user, $data): AgreementVersion {
            $agreement = Agreement::query()->lockForUpdate()->findOrFail($agreement->id);
            $this->assertNegotiating($agreement);
            $this->assertCurrentVersion($agreement, (int) $data['version_id']);
            $task = Task::query()->lockForUpdate()->findOrFail($agreement->task_id);

            return $this->addVersion($agreement, $user, $task, $data, $data['comment'] ?? null, (int) $agreement->versions()->max('version_number') + 1);
        });
    }

    public function confirm(Agreement $agreement, User $user, int $versionId): void
    {
        DB::transaction(function () use ($agreement, $user, $versionId): void {
            $agreement = Agreement::query()->lockForUpdate()->findOrFail($agreement->id);
            $this->assertNegotiating($agreement);
            $this->assertCurrentVersion($agreement, $versionId);
            $version = AgreementVersion::query()->lockForUpdate()->findOrFail($versionId);
            $this->confirmVersion($version, $user);

            $partyIds = [$agreement->poster_id, $agreement->student_id];
            $confirmedIds = $version->confirmations()->whereIn('user_id', $partyIds)->pluck('user_id')->all();
            if (count(array_unique($confirmedIds)) === 2) {
                if (! hash_equals($version->content_hash, $this->hasher->hash($version))) {
                    throw ValidationException::withMessages(['agreement' => 'The agreement record could not be verified. Please contact an administrator.']);
                }
                $agreement->current_version_id = $version->id;
                $agreement->status = AgreementStatus::Agreed;
                $agreement->locked_at = now();
                $agreement->save();

                $task = Task::query()->lockForUpdate()->findOrFail($agreement->task_id);
                if ($task->status !== TaskStatus::Assigned) {
                    throw ValidationException::withMessages(['agreement' => 'The task is no longer available for agreement.']);
                }
                $task->status = TaskStatus::InProgress;
                $task->save();
            }
        });
    }

    public function sendBack(Agreement $agreement, User $user, int $versionId, string $body): void
    {
        DB::transaction(function () use ($agreement, $user, $versionId, $body): void {
            $agreement = Agreement::query()->lockForUpdate()->findOrFail($agreement->id);
            $this->assertNegotiating($agreement);
            $this->assertCurrentVersion($agreement, $versionId);
            $message = new AgreementMessage;
            $message->agreement_id = $agreement->id;
            $message->user_id = $user->id;
            $message->body = $body;
            $message->created_at = now();
            $message->save();
            $this->scanner->flag($message, $user, 'body', $body);
        });
    }

    public function cancel(Agreement $agreement, User $user, string $reason): void
    {
        DB::transaction(function () use ($agreement, $user, $reason): void {
            $agreement = Agreement::query()->lockForUpdate()->findOrFail($agreement->id);
            $this->assertNegotiating($agreement);

            $agreement->status = AgreementStatus::Cancelled;
            $agreement->cancelled_at = now();
            $agreement->cancelled_by = $user->id;
            $agreement->cancellation_reason = $reason;
            $agreement->save();

            $task = Task::query()->lockForUpdate()->findOrFail($agreement->task_id);
            $application = Application::query()->lockForUpdate()->findOrFail($agreement->application_id);
            $application->status = ApplicationStatus::Cancelled;
            $application->responded_at = now();
            $application->save();
            $task->status = TaskStatus::Cancelled;
            $task->save();
        });
    }

    private function addVersion(Agreement $agreement, User $user, Task $task, array $data, ?string $comment, int $number): AgreementVersion
    {
        $version = new AgreementVersion;
        $version->agreement_id = $agreement->id;
        $version->version_number = $number;
        $version->proposed_by = $user->id;
        $version->deliverables = $data['deliverables'];
        $version->terms = $data['terms'];
        $version->final_amount = $data['final_amount'];
        $version->start_date = $data['start_date'] ?? null;
        $version->due_date = $data['due_date'];
        $version->work_mode = $task->work_mode;
        $version->workplace_name = $task->workplace_name;
        $version->workplace_address = $task->workplace_address;
        $version->payment_arrangement = $data['payment_arrangement'];
        $version->comment = $comment;
        $version->created_at = now();
        $version->content_hash = $this->hasher->hash([
            'deliverables' => $version->deliverables,
            'terms' => $version->terms,
            'final_amount' => number_format((float) $version->final_amount, 2, '.', ''),
            'start_date' => $version->start_date?->toDateString(),
            'due_date' => $version->due_date?->toDateString(),
            'work_mode' => $version->work_mode,
            'workplace_name' => $version->workplace_name,
            'workplace_address' => $version->workplace_address,
            'payment_arrangement' => $version->payment_arrangement,
        ]);
        $version->save();
        $this->scanner->flag($version, $user, 'terms', $version->terms);
        if ($version->comment) $this->scanner->flag($version, $user, 'comment', $version->comment);

        $agreement->current_version_id = $version->id;
        $agreement->save();
        $this->confirmVersion($version, $user);

        return $version;
    }

    private function confirmVersion(AgreementVersion $version, User $user): void
    {
        if (! $version->confirmations()->where('user_id', $user->id)->exists()) {
            $confirmation = new AgreementConfirmation;
            $confirmation->agreement_version_id = $version->id;
            $confirmation->user_id = $user->id;
            $confirmation->confirmed_at = now();
            $confirmation->save();
        }
    }

    private function assertNegotiating(Agreement $agreement): void
    {
        if ($agreement->status !== AgreementStatus::Negotiating) {
            throw ValidationException::withMessages(['agreement' => 'This agreement is no longer being negotiated.']);
        }
    }

    private function assertCurrentVersion(Agreement $agreement, int $versionId): void
    {
        if ($agreement->current_version_id !== $versionId) {
            throw ValidationException::withMessages(['version_id' => 'This agreement changed while you were viewing it. Reload the page and try again.']);
        }
    }
}
