<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\ChunkedUploadController;

// Health check endpoint
Route::get('/health', function() {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
        'cors' => [
            'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none',
            'host' => $_SERVER['HTTP_HOST'] ?? 'none',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'none'
        ]
    ]);
});

// API Routes
Route::prefix('api')->group(function () {
    // Email routes
    Route::post('send-email', [EmailController::class, 'sendInquiry']);

    // Chunked file upload
    Route::post('upload-chunk', [ChunkedUploadController::class, 'uploadChunk']);
});
