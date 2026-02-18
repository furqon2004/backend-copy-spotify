<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Paksa semua request dianggap JSON
        $request->headers->set('Accept', 'application/json');

        try {
            return $next($request);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }
    }
}
