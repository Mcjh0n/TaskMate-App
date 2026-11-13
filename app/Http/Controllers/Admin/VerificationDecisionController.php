<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerificationDecisionRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class VerificationDecisionController extends Controller
{
    public function __invoke(VerificationDecisionRequest $request, User $user): RedirectResponse
    {
        $decision = VerificationStatus::from($request->validated('decision'));
        $note = $decision === VerificationStatus::Verified ? null : $request->validated('note');
        $action = match ($decision) {
            VerificationStatus::Verified => 'user_verified',
            VerificationStatus::Rejected => 'user_rejected',
            VerificationStatus::MoreInfoRequested => 'user_more_info_requested',
            VerificationStatus::Pending => throw new \LogicException('Pending is not an administrator decision.'),
        };

        DB::transaction(function () use ($request, $user, $decision, $note, $action): void {
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
        });

        return to_route('admin.users.show', $user)->with('status', 'The account verification decision was saved.');
    }
}
