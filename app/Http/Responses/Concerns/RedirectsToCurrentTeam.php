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

        // Some existing or locally seeded accounts may not have a personal team.
        // Keep authentication usable without making TaskMate depend on Teams.
        if (! $team) {
            return route('taskmate.dashboard', [], false);
        }

        URL::defaults(['current_team' => $team->slug]);

        return "/{$team->slug}{$redirect}";
    }

}
