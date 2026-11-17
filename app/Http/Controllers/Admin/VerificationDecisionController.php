<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerificationDecisionRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use App\Services\TaskMateNotifications;

class VerificationDecisionController extends Controller
{
    public function __invoke(VerificationDecisionRequest $request, User $user, TaskMateNotifications $alerts): RedirectResponse
    {
        $decision = VerificationStatus::from($request->validated('decision'));
        $note = $decision === VerificationStatus::Verified ? null : $request->validated('note');
        $action = match ($decision) {
            VerificationStatus::Verified => 'user_verified',
            VerificationStatus::Rejected => 'user_rejected',
            VerificationStatus::MoreInfoRequested => 'user_more_info_requested',
            VerificationStatus::Pending => throw new \LogicException('Pending is not an administrator decision.'),
        };

        DB::transaction(function () use ($request, $user, $decision, $note, $action, $alerts): void {
            $user->verification_status = $decision;
            $user->verification_note = $note;

            if ($decision === VerificationStatus::Verified) {
                $user->verified_at = now();
                $user->verified_by = $request->user()->id;
            } else {
                $user->verified_at = null;
                $user->verified_by = null;
            }

            $user->save();
            $user->adminActions()->create([
                'admin_id' => $request->user()->id,
                'action' => $action,
                'note' => $note,
            ]);
            $event = match ($decision) {
                VerificationStatus::Verified => 'account.approved',
                VerificationStatus::Rejected => 'account.rejected',
                VerificationStatus::MoreInfoRequested => 'account.more_info_requested',
                default => null,
            };
            if ($event) $alerts->send($user, $event, '/notifications', ['user_id' => $user->id], actorId: $request->user()->id);
        });

        return to_route('admin.users.show', $user)->with('status', 'The account verification decision was saved.');
    }
}
