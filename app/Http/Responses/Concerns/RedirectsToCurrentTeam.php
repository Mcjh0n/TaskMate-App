<?php

namespace App\Http\Responses\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentTeam
{
    protected function redirectPathForCurrentTeam(Request $request, string $redirect): string
    {
        $user = $request->user();

        abort_if(! $user, 403);

        // TaskMate administrators use the separate admin area and do not need a Teams record.
        if ($user->isAdmin()) {
            return route('admin.dashboard', [], false);
        }

        $team = $user->currentTeam ?? $user->personalTeam();
        if (! $team) {
            return route('taskmate.dashboard', [], false);
        }

        URL::defaults(['current_team' => $team->slug]);

        return "/{$team->slug}{$redirect}";
    }

    protected function dashboardPathForUser(Request $request): string
    {
        $user = $request->user();
        abort_if(! $user, 403);

        return $user->isAdmin() ? route('admin.dashboard', [], false) : route('taskmate.dashboard', [], false);
    }
}
