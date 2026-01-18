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
            'https://backend-g7yc.onrender.com',
            'http://localhost:8000',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8000',
        ];

        $requestHeaders = $request->headers->get('Access-Control-Request-Headers');
        $allowHeaders = $requestHeaders ?: 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin';

        // Allow all origins in development, restrict in production
        $allowOrigin = in_array($origin, $allowedOrigins, true) ? $origin : $allowedOrigins[0];

        $headers = [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Headers' => $allowHeaders,
            'Access-Control-Allow-Credentials' => 'true',
            'Vary' => 'Origin',
        ];

        if ($request->isMethod('OPTIONS')) {
            return response()->json([], 200, $headers);
        }
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
