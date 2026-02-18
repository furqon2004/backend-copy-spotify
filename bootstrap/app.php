<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ArtistMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'artist' => ArtistMiddleware::class,
        ]);
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Menjalankan pembersihan token Sanctum yang expired setiap jam
        $schedule->command('sanctum:prune-expired --hours=24')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        });
    })->create();