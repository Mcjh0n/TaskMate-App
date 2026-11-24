<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerificationDecisionRequest;
use App\Models\User;
use App\Services\TaskMateNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class VerificationDecisionController extends Controller
{
    public function __invoke(VerificationDecisionRequest $request, User $user, TaskMateNotifications $alerts): RedirectResponse
    {
        $decision = VerificationStatus::from($request->validated('decision'));
        $note = $decision === VerificationStatus::Verified ? null : $request->validated('note');
        $decisionData = [
            VerificationStatus::Verified->value => ['action' => 'user_verified', 'event' => 'account.approved'],
            VerificationStatus::Rejected->value => ['action' => 'user_rejected', 'event' => 'account.rejected'],
            VerificationStatus::MoreInfoRequested->value => ['action' => 'user_more_info_requested', 'event' => 'account.more_info_requested'],
        ][$decision->value] ?? throw new \LogicException('Pending is not an administrator decision.');
        $action = $decisionData['action'];

        DB::transaction(function () use ($request, $user, $decision, $note, $action, $decisionData, $alerts): void {
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
            $alerts->send($user, $decisionData['event'], '/notifications', ['user_id' => $user->id], actorId: $request->user()->id);
        });

        return to_route('admin.users.show', $user)->with('status', 'The account verification decision was saved.');
    }
}
