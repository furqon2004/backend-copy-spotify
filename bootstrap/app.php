<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule; // Pastikan import ini ada

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tempat mendaftarkan middleware global atau alias
    })
    ->withSchedule(function (Schedule $schedule) {
        // Menjalankan pembersihan token Sanctum yang expired setiap jam
        $schedule->command('sanctum:prune-expired --hours=24')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Tempat penanganan exception kustom
    })->create();