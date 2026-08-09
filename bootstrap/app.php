<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->routeIs('list.contribute.*') || $request->routeIs('list.free.*')) {
                return route('login', ['bedoeling' => 'bijdragen']);
            }

            if ($request->routeIs('account.*') || $request->is('mijn')) {
                return route('login', ['bedoeling' => 'opvolgen']);
            }

            return route('login');
        });

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // TIJDELIJK: 404-debugging — nadien verwijderen
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            Log::warning('404 debug', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'referer' => $request->header('referer'),
                'inertia' => $request->header('x-inertia'),
                'user' => $request->user()?->email,
                'intended' => $request->hasSession() ? $request->session()->get('url.intended') : null,
            ]);

            return null;
        });
    })->create();
