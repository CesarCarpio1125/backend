<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:32768', // 32MB
        ]);

        $path = $request->file('file')->store('temp', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'original_name' => $request->file('file')->getClientOriginalName()
        ]);
    }
}
