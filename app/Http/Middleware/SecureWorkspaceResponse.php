<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureWorkspaceResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->user() || $request->is('api/*') || $request->routeIs('login', 'register', 'password.*', 'two-factor.*', 'social.*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
