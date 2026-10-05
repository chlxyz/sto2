<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        // Gives every api/* route per-IP throttling. Without it there was no
        // framework rate limiting at all, and the OTP limiter was keyed on
        // email only -- so one IP could spray codes at a whole address list.
        $middleware->throttleApi();
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureIsAdmin::class]);
        // The web login route is named 'loginForm', but Authenticate's default
        // guest redirect resolves route('login') -- which threw a 500 on every
        // gated page until this pointed at the real path.
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Belt-and-braces on APP_DEBUG=false: debug on outside local renders an
        // Ignition page containing APP_KEY, DB_PASSWORD and ADMIN_EMAIL. Only
        // intercepts that exact case -- validation and HTTP exceptions (403,
        // 422, 404) pass straight through so forms and status codes still work.
        $exceptions->render(function (Throwable $e, Request $request) {
            $unsafe = config('app.debug')
                && ! app()->isLocal()
                && ! $e instanceof HttpExceptionInterface
                && ! $e instanceof ValidationException;

            if (! $unsafe) {
                return null;
            }

            return $request->expectsJson()
                ? response()->json(['message' => 'Server error.'], 500)
                : response('Server error.', 500);
        });
    })->create();
