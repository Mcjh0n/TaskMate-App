<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isVerified()) {
            return to_route('dashboard')->with('status', 'Your account is awaiting verification by an administrator.');
        }

        return $next($request);
    }
}
