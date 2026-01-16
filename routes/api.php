<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\ChunkedUploadController;

// Health check – para verificar que la API responde
Route::get('/health', fn() => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toISOString(),
    'environment' => app()->environment()
]));

// Email routes
Route::post('send-email', [EmailController::class, 'sendInquiry']);

// Chunked file upload
Route::post('upload-chunk', [ChunkedUploadController::class, 'uploadChunk']);
