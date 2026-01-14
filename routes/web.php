<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmailController;

// CORS middleware
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');

Route::get('/', function () {
    return response()->json(['message' => 'API is working']);
});

// Email routes
Route::post('/send-email', [EmailController::class, 'sendInquiry']);
