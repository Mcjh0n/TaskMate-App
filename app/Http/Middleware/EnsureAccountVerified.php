<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('taskmate.require_verification', true) && ! $request->user()?->isVerified()) {
            return to_route('dashboard')->with('status', 'Your account must be verified before you can continue.');
        }

        return $next($request);
    }
}
