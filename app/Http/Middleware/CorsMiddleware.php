<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $requestHeaders = $request->headers->get('Access-Control-Request-Headers');
        $allowHeaders = $requestHeaders ?: 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin';

        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Headers' => $allowHeaders,
        ];

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders($headers);
        }

        $response = $next($request);
        $response->headers->add($headers);

        return $response;
    }
}
