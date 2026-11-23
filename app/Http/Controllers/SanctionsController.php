<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcknowledgeWarningRequest;
use App\Models\UserSanction;
use App\Services\SanctionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SanctionsController extends Controller
{
    public function index(Request $request, SanctionService $service): Response
    {
        $user = $request->user();
        $items = $user->sanctions()->latest()->get()->map(fn (UserSanction $item) => [
            'id' => $item->id,
            'type' => $item->type->value,
            'reason' => $item->reason,
            'startsAt' => $item->starts_at?->toDateTimeString(),
            'endsAt' => $item->ends_at?->toDateTimeString(),
            'liftedAt' => $item->lifted_at?->toDateTimeString(),
            'acknowledged' => (bool) $item->acknowledged_at,
            'active' => $item->lifted_at === null && $item->starts_at?->isPast() && ($item->ends_at === null || $item->ends_at->isFuture()),
        ])->values();

        return Inertia::render('sanctions/index', [
            'sanctions' => $items,
            'activeSuspension' => $service->suspended($user) ? ['reason' => $service->suspended($user)->reason, 'endsAt' => $service->suspended($user)->ends_at?->toDateTimeString()] : null,
            'contact' => config('taskmate.moderation.sanction_notice_contact'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function acknowledge(AcknowledgeWarningRequest $request, UserSanction $sanction): RedirectResponse
    {
        $sanction->acknowledged_at = now();
        $sanction->save();

        return back()->with('status', 'You acknowledged the warning.');
    }
}
