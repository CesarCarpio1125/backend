<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class ChunkedUploadController extends Controller
{
    public function uploadChunk(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file',
                'chunkNumber' => 'required|numeric',
                'totalChunks' => 'required|numeric',
                'originalName' => 'required|string',
                'totalSize' => 'required|numeric',
                'identifier' => 'required|string',
                'filename' => 'nullable|string',
            ]);

            $file = $request->file('file');
            $chunkNumber = (int)$request->input('chunkNumber');
            $totalChunks = (int)$request->input('totalChunks');
            $originalName = $request->input('originalName');
            $totalSize = (int)$request->input('totalSize');
            $identifier = $request->input('identifier');
            $filename = $request->input('filename', $identifier . '.pdf');

            // Directorio temporal para los fragmentos
            $chunkDir = 'chunks/' . $identifier;
            $fullChunkDir = storage_path('app/' . $chunkDir);
            
            // Asegurarse de que el directorio existe
            if (!file_exists($fullChunkDir)) {
                if (!mkdir($fullChunkDir, 0755, true)) {
                    throw new \Exception('No se pudo crear el directorio temporal');
                }
            }

            // Guardar el fragmento
            $chunkPath = $fullChunkDir . '/' . $chunkNumber;
            
            // Mover el archivo subido a la ubicación temporal
            if (!$file->move($fullChunkDir, $chunkNumber)) {
                throw new \Exception('Error al guardar el fragmento');
            }

            // Si es el último fragmento, combinar los archivos
            if ($chunkNumber == $totalChunks - 1) {
                return $this->combineChunks($identifier, $filename, $originalName);
            }

            return response()->json([
                'success' => true,
                'chunk' => $chunkNumber,
                'message' => 'Fragmento subido correctamente',
                'chunk_number' => $chunkNumber,
                'total_chunks' => $totalChunks
            ]);
        } catch (\Exception $e) {
            Log::error('Error en uploadChunk: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el fragmento: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    private function combineChunks($identifier, $filename, $originalName)
    {
        try {
            $chunkDir = 'chunks/' . $identifier;
            $fullChunkDir = storage_path('app/' . $chunkDir);
            
            // Obtener la lista de fragmentos
            $chunks = [];
            if (is_dir($fullChunkDir)) {
                $chunks = array_diff(scandir($fullChunkDir), ['.', '..']);
                natsort($chunks);
                $chunks = array_values($chunks);
            }
            
            if (empty($chunks)) {
                throw new \Exception('No se encontraron fragmentos para combinar');
            }

            // Crear directorio de destino si no existe
            $finalDir = 'public/uploads';
            $fullFinalDir = storage_path('app/' . $finalDir);
            
            if (!file_exists($fullFinalDir)) {
                if (!mkdir($fullFinalDir, 0755, true)) {
                    throw new \Exception('No se pudo crear el directorio de destino');
                }
            }

            // Crear el archivo final
            $finalPath = $finalDir . '/' . $filename;
            $finalFullPath = storage_path('app/' . $finalPath);
            
            // Abrir el archivo final para escritura
            $finalFile = fopen($finalFullPath, 'wb');
            if ($finalFile === false) {
                throw new \Exception('No se pudo crear el archivo final');
            }

            try {
                // Combinar los fragmentos
                foreach ($chunks as $chunk) {
                    $chunkPath = $fullChunkDir . '/' . $chunk;
                    $chunkContent = file_get_contents($chunkPath);
                    if ($chunkContent === false) {
                        throw new \Exception("Error al leer el fragmento: {$chunk}");
                    }
                    
                    if (fwrite($finalFile, $chunkContent) === false) {
                        throw new \Exception('Error al escribir en el archivo final');
                    }
                }
            } finally {
                fclose($finalFile);
            }

            // Verificar que el archivo final existe y tiene contenido
            if (!file_exists($finalFullPath) || filesize($finalFullPath) === 0) {
                throw new \Exception('El archivo final no se creó correctamente');
            }

            // Eliminar los fragmentos
            $this->deleteDirectory($fullChunkDir);

            // Devolver la ruta relativa sin 'public/'
            $relativePath = 'uploads/' . $filename;
            
            return response()->json([
                'success' => true,
                'path' => $relativePath, // Ruta relativa sin 'public/'
                'url' => asset('storage/' . $relativePath),
                'original_name' => $originalName,
                'size' => filesize($finalFullPath),
                'message' => 'Archivo subido y combinado correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en combineChunks: ' . $e->getMessage(), [
                'identifier' => $identifier,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Limpiar archivos temporales en caso de error
            if (isset($fullChunkDir) && is_dir($fullChunkDir)) {
                $this->deleteDirectory($fullChunkDir);
            }
            if (isset($finalFullPath) && file_exists($finalFullPath)) {
                @unlink($finalFullPath);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error al combinar los fragmentos: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Elimina un directorio y su contenido de forma recursiva
     *
     * @param string $dir Ruta del directorio a eliminar
     * @return bool
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
