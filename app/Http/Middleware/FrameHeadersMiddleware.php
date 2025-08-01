<?php

namespace App\Http\Middleware;

use Closure;

class FrameHeadersMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('Content-Security-Policy', 'frame-ancestors *');
        return $response;
    }
}