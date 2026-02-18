<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ArtistMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->artist) {
            return response()->json(['message' => 'Forbidden. Artist access required.'], 403);
        }

        return $next($request);
    }
}
