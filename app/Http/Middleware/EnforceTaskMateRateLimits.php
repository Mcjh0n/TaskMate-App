<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnforceTaskMateRateLimits
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $bucket = match (true) {
            $routeName === 'register.store' => 'register',
            $routeName === 'applications.store' => 'apply',
            in_array($routeName, [
                'agreements.reports.store', 'agreements.problem-reports.store', 'tasks.problem-reports.store',
                'applications.problem-reports.store', 'users.problem-reports.store',
            ], true) => 'report',
            $routeName === 'reports.evidence' => 'evidence_download',
            default => null,
        };

        if ($bucket !== null) {
            $limits = config('taskmate.rate_limits.'.$bucket);
            $key = $bucket.'|'.($request->user()?->id ?? $request->ip());
            if (RateLimiter::tooManyAttempts($key, (int) $limits['max_attempts'])) {
                abort(429, 'Too many requests. Please wait and try again.');
            }
            RateLimiter::hit($key, (int) $limits['decay_seconds']);
        }

        return $next($request);
    }
}
