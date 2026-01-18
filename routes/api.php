<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmailController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Grupo de rutas API con middleware CORS
Route::middleware([\App\Http\Middleware\Cors::class])->group(function () {
    // Ruta de verificación de salud
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'API funcionando correctamente',
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment()
        ]);
    })->name('health');

    // Ruta de prueba
    Route::post('/test', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Prueba POST exitosa',
            'data' => request()->all(),
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment()
        ]);
    })->name('test');

    // Ruta para enviar correos
    Route::post('/send-email', [EmailController::class, 'sendInquiry']);
});
