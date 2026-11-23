<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanBrowse
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canBrowse(), 403, 'Only student accounts can browse tasks.');

        return $next($request);
    }
}
