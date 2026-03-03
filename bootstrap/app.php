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
        $middleware->redirectGuestsTo(fn() => '/login');
    })
    ->withSchedule(function (Schedule $schedule) {
        // Menjalankan pembersihan token Sanctum yang expired setiap jam
        $schedule->command('sanctum:prune-expired --hours=24')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $origin = $request->header('Origin');
                $allowedOrigins = config('cors.allowed_origins', ['*']);

                // Check if origin is allowed
                $corsOrigin = in_array('*', $allowedOrigins)
                    ? '*'
                    : (in_array($origin, $allowedOrigins) ? $origin : null);

                $response = response()->json(['message' => 'Unauthenticated.'], 401);

                if ($corsOrigin) {
                    $response->headers->set('Access-Control-Allow-Origin', $corsOrigin);
                    $response->headers->set('Access-Control-Allow-Credentials', 'true');
                    $response->headers->set('Access-Control-Allow-Headers', '*');
                }

                return $response;
            }
            return redirect('/login');
        });
    })->create();