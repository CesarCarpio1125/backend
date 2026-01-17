<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API funcionando correctamente',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment()
    ]);
})->name('health');

Route::post('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Prueba POST exitosa',
        'data' => request()->all(),
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment()
    ]);
})->name('test');
