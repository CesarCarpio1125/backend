<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ChunkedUploadController extends Controller
{
    public function uploadChunk(Request $request)
    {
        try {
            // Validación de campos
            $validated = $request->validate([
                'file' => 'required|file',
                'chunkNumber' => 'required|integer|min:1',
                'totalChunks' => 'required|integer|min:1',
                'originalName' => 'required|string|max:255',
                'totalSize' => 'required|integer|min:1',
                'identifier' => 'required|string|max:255',
                'filename' => 'required|string|max:255',
                'fileType' => 'required|string|max:100',
            ]);

            $file = $request->file('file');
            $chunkNumber = (int)$request->input('chunkNumber');
            $totalChunks = (int)$request->input('totalChunks');
            $identifier = $request->input('identifier');
            $filename = $request->input('filename');

            // Directorio temporal para los fragmentos
            $chunkDir = 'chunks/' . $identifier;
            $chunkPath = $chunkDir . '/' . $chunkNumber;

            // Asegurar que el directorio existe
            if (!Storage::exists($chunkDir)) {
                Storage::makeDirectory($chunkDir, 0755, true);
            }

            // Guardar el fragmento
            $file->storeAs($chunkDir, $chunkNumber);

            // Si es el último fragmento, combinar
            if ($chunkNumber === $totalChunks) {
                return $this->combineChunks($identifier, $filename, $request->input('originalName'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Chunk uploaded successfully',
                'chunkNumber' => $chunkNumber,
                'totalChunks' => $totalChunks
            ]);

        } catch (\Exception $e) {
            Log::error('Upload chunk error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['file']) // Excluir el archivo del log
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing chunk: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function combineChunks($identifier, $filename, $originalName)
    {
        try {
            $chunkDir = 'chunks/' . $identifier;
            $finalDir = 'public/uploads';
            $finalPath = $finalDir . '/' . $filename;

            // Asegurar que el directorio de destino existe
            if (!Storage::exists($finalDir)) {
                Storage::makeDirectory($finalDir, 0755, true);
            }

            // Obtener y ordenar los fragmentos
            $chunks = Storage::files($chunkDir);
            natsort($chunks);

            if (empty($chunks)) {
                throw new \Exception('No chunks found to combine');
            }

            // Crear el archivo final
            $finalFullPath = Storage::path($finalPath);
            $finalFile = fopen($finalFullPath, 'wb');

            if ($finalFile === false) {
                throw new \Exception('Could not create final file');
            }

            try {
                // Combinar los fragmentos
                foreach ($chunks as $chunk) {
                    $chunkContent = Storage::get($chunk);
                    if (fwrite($finalFile, $chunkContent) === false) {
                        throw new \Exception("Failed to write chunk: {$chunk}");
                    }
                }
            } finally {
                fclose($finalFile);
            }

            // Verificar que el archivo final existe
            if (!Storage::exists($finalPath)) {
                throw new \Exception('Final file was not created');
            }

            // Limpiar fragmentos
            Storage::deleteDirectory($chunkDir);

            // Crear enlace simbólico si no existe
            $publicPath = public_path('storage');
            if (!file_exists($publicPath)) {
                \Artisan::call('storage:link');
            }

            return response()->json([
                'success' => true,
                'path' => 'storage/' . $filename,
                'url' => asset('storage/' . $filename),
                'original_name' => $originalName,
                'size' => Storage::size($finalPath),
                'message' => 'File uploaded and combined successfully'
            ]);

        } catch (\Exception $e) {
            // Limpiar en caso de error
            if (isset($chunkDir)) {
                Storage::deleteDirectory($chunkDir);
            }
            if (isset($finalPath) && Storage::exists($finalPath)) {
                Storage::delete($finalPath);
            }

            Log::error('Combine chunks error: ' . $e->getMessage(), [
                'identifier' => $identifier,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
