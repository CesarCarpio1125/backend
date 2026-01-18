<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'https://backend-g7yc.onrender.com',
            'https://innosure.vercel.app',
            'https://www.innosure.com.mx'
        ];

        $origin = $request->headers->get('Origin');
        \Log::info('CORS Request Origin:', [
            'origin' => $origin,
            'method' => $request->method(),
            'path' => $request->path(),
            'headers' => $request->headers->all()
        ]);

        // Determinar si el origen es permitido
        $isAllowedOrigin = $origin && in_array($origin, $allowedOrigins, true);

        // Si es una solicitud preflight (OPTIONS)
        if ($request->isMethod('OPTIONS')) {
            if (!$isAllowedOrigin) {
                // Opcional: rechazar explícitamente orígenes no permitidos
                return response('Forbidden', 403);
            }

            return response('', 204)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Vary', 'Origin');
        }

        // Continuar con la solicitud normal
        $response = $next($request);

        // Solo añadir cabeceras CORS si el origen es permitido
        if ($isAllowedOrigin) {
            $response->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Vary', 'Origin');
        }

        return $response;
    }
}
