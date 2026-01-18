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

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment()
    ]);
})->name('health');

// Test endpoint
Route::post('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Test endpoint is working',
        'data' => request()->all(),
        'timestamp' => now()->toISOString()
    ]);
})->name('test');

// Email endpoint
Route::post('/send-email', [EmailController::class, 'sendInquiry'])->name('send-email');
