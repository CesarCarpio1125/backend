<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmailController;

// Grupo de rutas API con middleware CORS
Route::middleware(['cors'])->group(function () {
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
