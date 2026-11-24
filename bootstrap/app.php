<?php

use App\Http\Middleware\EnforceTaskMateRateLimits;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAccountVerified;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureCanApply;
use App\Http\Middleware\EnsureCanBrowse;
use App\Http\Middleware\EnsureCanPost;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTeamUrlDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Keep Vite preload hints in HTML instead of emitting a large FastCGI Link header.
        $middleware->web(append: [
            EnsureAccountActive::class,
            EnforceTaskMateRateLimits::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            SetTeamUrlDefaults::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'account.verified' => EnsureAccountVerified::class,
            'can.post' => EnsureCanPost::class,
            'can.apply' => EnsureCanApply::class,
            'can.browse' => EnsureCanBrowse::class,
            'account.active' => EnsureAccountActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['body']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function (Response $response, Throwable $exception) {
            $request = request();
            $status = $response->getStatusCode();
            if ($status >= 500 && app()->environment('local') && config('app.debug')) {
                return $response;
            }
            if ($request->expectsJson() || ! in_array($status, [403, 404, 419, 429, 500], true)) {
                return $response;
            }

            return Inertia::render('errors/status', ['status' => $status])->toResponse($request)->setStatusCode($status);
        });
    })->create();
