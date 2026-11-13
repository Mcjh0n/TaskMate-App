<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Enums\VerificationStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'status' => $request->session()->get('status'),
            'verificationNote' => in_array($request->user()->verification_status, [VerificationStatus::Rejected, VerificationStatus::MoreInfoRequested], true)
                ? $request->user()->verification_note
                : null,
            'verifiedAt' => $request->user()->verified_at?->toDateTimeString(),
        ]);
    }
}
