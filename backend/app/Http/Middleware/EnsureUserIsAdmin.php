<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     * Protects routes that require admin privileges.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Neautorizovaný prístup. Musíte byť prihlásený.'
            ], 401);
        }

        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Prístup zamietnutý. Táto funkcia je dostupná iba pre administrátorov.'
            ], 403);
        }

        return $next($request);
    }
}
