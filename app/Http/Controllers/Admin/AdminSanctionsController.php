<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\SanctionType;
use App\Enums\TaskStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IssueSanctionRequest;
use App\Http\Requests\Admin\LiftSanctionRequest;
use App\Models\AdminAction;
use App\Models\Agreement;
use App\Models\Task;
use App\Models\User;
use App\Models\UserSanction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminSanctionsController extends Controller
{
    public function issue(IssueSanctionRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_if($user->isAdmin() || $user->id === $request->user()->id, 403);
            $report = $request->filled('report_id') ? \App\Models\Report::lockForUpdate()->findOrFail($request->integer('report_id')) : null;
            if ($report) abort_unless($request->user()->can('review', $report), 403);
            $action = $request->validated('action');
            if ($action === 'remove_post') {
                $task = Task::query()->lockForUpdate()->where('poster_id', $user->id)->findOrFail($request->validated('task_id'));
                $agreement = $task->agreement;
                if (in_array($task->status, [TaskStatus::InProgress, TaskStatus::Completed], true)
                    || ($agreement && $agreement->status === AgreementStatus::Agreed && $agreement->locked_at)) {
                    throw ValidationException::withMessages(['task_id' => 'A task with a locked agreement cannot be removed.']);
                }
                $now = now();
                $task->applications()->where('status', ApplicationStatus::Pending->value)->update(['status' => ApplicationStatus::Declined->value, 'responded_at' => $now, 'updated_at' => $now]);
                $task->status = TaskStatus::Removed;
                $task->removal_reason = $request->validated('reason');
                $task->removed_by = $request->user()->id;
                $task->removed_at = $now;
                $task->save();
                $task->adminActions()->create(['admin_id' => $request->user()->id, 'action' => 'task_removed', 'note' => $request->validated('internal_note'), 'metadata' => ['reason' => $request->validated('reason'), 'report_id' => $report?->id]]);
                return;
            }
            if ($action === 'require_verification') {
                $user->verification_status = VerificationStatus::MoreInfoRequested;
                $user->verification_note = $request->validated('reason');
                $user->save();
                $this->log($user, $request->user(), 'additional_verification_required', $request->validated('internal_note'), $report, ['user_reason' => $request->validated('reason')]);
                return;
            }
            $sanction = new UserSanction;
            $sanction->user_id = $user->id;
            $sanction->type = $action === 'suspend' ? SanctionType::Suspension : SanctionType::from($action);
            $sanction->reason = $request->validated('reason');
            $sanction->internal_note = $request->validated('internal_note');
            $sanction->starts_at = now();
            $sanction->ends_at = $action === 'suspend' ? $request->date('ends_at') : null;
            $sanction->issued_by = $request->user()->id;
            $sanction->report_id = $report?->id;
            $sanction->save();
            $this->log($user, $request->user(), 'sanction_'.$action, $request->validated('internal_note'), $report, ['sanction_id' => $sanction->id, 'reason' => $sanction->reason]);
        });
        return back()->with('status', 'The platform action was recorded.');
    }

    public function lift(LiftSanctionRequest $request, UserSanction $sanction): RedirectResponse
    {
        DB::transaction(function () use ($request, $sanction): void {
            $sanction = UserSanction::query()->lockForUpdate()->findOrFail($sanction->id);
            abort_if($sanction->lifted_at || $sanction->user->isAdmin() || $sanction->user_id === $request->user()->id, 403);
            $sanction->lifted_at = now();
            $sanction->lifted_by = $request->user()->id;
            $sanction->lift_note = $request->validated('note');
            $sanction->save();
            $this->log($sanction->user, $request->user(), 'sanction_lifted', $request->validated('note'), $sanction->report, ['sanction_id' => $sanction->id]);
        });
        return back()->with('status', 'The sanction was lifted.');
    }

    private function log(User $subject, User $admin, string $action, string $note, $report = null, array $metadata = []): void
    {
        $entry = new AdminAction;
        $entry->admin_id = $admin->id;
        $entry->action = $action;
        $entry->note = $note;
        $entry->metadata = $metadata + ['report_id' => $report?->id];
        $entry->subject()->associate($subject);
        $entry->save();
    }
}
