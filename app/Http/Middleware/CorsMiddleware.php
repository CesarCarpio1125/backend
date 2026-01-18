<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Lista de orígenes permitidos
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'https://backend-g7yc.onrender.com',
            // Agrega aquí otros dominios si es necesario
        ];

        // Obtener el origen de la petición
        $origin = $request->header('Origin');
        
        // Verificar si el origen está permitido
        if (in_array($origin, $allowedOrigins)) {
            $headers = [
                'Access-Control-Allow-Origin'      => $origin,
                'Access-Control-Allow-Methods'     => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age'           => '86400',
                'Vary'                             => 'Origin'
            ];

            // Para peticiones OPTIONS, devolver solo los headers
            if ($request->isMethod('OPTIONS')) {
                return response()->json(['status' => 'success'], 200, $headers);
            }

            // Para otras peticiones, continuar y agregar los headers
            $response = $next($request);
            
            // Agregar los headers a la respuesta
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }

            return $response;
        }

        // Si el origen no está permitido, continuar sin modificar la respuesta
        return $next($request);
    }
}
