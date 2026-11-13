<?php

namespace App\Http\Middleware;

use App\Services\SanctionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->isAdmin()) return $next($request);
        $sanctions = app(SanctionService::class);
        if ($sanctions->banned($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $contact = config('taskmate.moderation.sanction_notice_contact');
            return redirect()->route('login')->with('status', 'Your account is banned. '.$contact);
        }
        if (! $sanctions->suspended($user)) return $next($request);

        if ($request->routeIs('dashboard', 'sanctions.mine', 'sanctions.acknowledge', 'logout', 'reports.mine')) return $next($request);
        if ($request->routeIs('reports.reply')) {
            $report = $request->route('report');
            if ($report && $user->can('reply', $report)) return $next($request);
        }
        if ($request->isMethod('GET') && $request->routeIs('reports.show', 'reports.evidence')) {
            $report = $request->route('report');
            if ($report && $user->can('view', $report)) return $next($request);
        }
        if ($request->isMethod('GET') && $request->routeIs('agreements.show')) return $next($request);
        if ($request->is('browse*')) abort(403, 'Browsing is unavailable while your account is suspended.');
        if (! $request->isMethod('GET')) abort(403, 'This action is unavailable while your account is suspended.');
        return $next($request);
    }
}
