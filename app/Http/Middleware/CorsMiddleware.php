<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = [
            'http://localhost:3000',
        ];

        $requestHeaders = $request->headers->get('Access-Control-Request-Headers');
        $allowHeaders = $requestHeaders ?: 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin';

        $allowOrigin = in_array($origin, $allowedOrigins, true) ? $origin : null;

        $headers = [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Headers' => $allowHeaders,
            'Access-Control-Allow-Credentials' => 'true',
            'Vary' => 'Origin',
        ];

        if ($request->isMethod('OPTIONS')) {
            if ($allowOrigin === null) {
                return response('', 204);
            }

            return response('', 204)->withHeaders($headers);
        }

        $response = $next($request);

        if ($allowOrigin !== null) {
            $response->headers->add($headers);
        }

        return $response;
    }
}
