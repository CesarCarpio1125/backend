<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\ChunkedUploadController;

// Email routes
Route::post('send-email', [EmailController::class, 'sendInquiry']);

// Chunked file upload
Route::post('upload-chunk', [ChunkedUploadController::class, 'uploadChunk']);
