<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanPost
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canPost(), 403, 'Your account type or verification status does not allow you to post tasks.');

        return $next($request);
    }
}
