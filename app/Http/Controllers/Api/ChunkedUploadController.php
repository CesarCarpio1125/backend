<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ChunkedUploadController extends Controller
{
    // Maximum file size in bytes (50MB)
    const MAX_FILE_SIZE = 50 * 1024 * 1024;
    
    // Allowed file types
    const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    public function uploadChunk(Request $request)
    {
        \Log::info('Upload chunk request received', [
            'headers' => $request->headers->all(),
            'input' => $request->except(['file'])
        ]);

        // Validate request
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'max:' . (self::MAX_FILE_SIZE / 1024), // Convert to KB
                'mimetypes:' . implode(',', self::ALLOWED_MIME_TYPES)
            ],
            'chunkNumber' => 'required|integer|min:1',
            'totalChunks' => 'required|integer|min:1',
            'originalName' => 'required|string|max:255',
            'totalSize' => 'required|integer|min:1|max:' . self::MAX_FILE_SIZE,
            'identifier' => 'required|string|max:255',
            'filename' => 'required|string|max:255',
            'fileType' => 'required|string|max:100|in:' . implode(',', self::ALLOWED_MIME_TYPES),
        ], [
            'file.mimetypes' => 'El tipo de archivo no es válido. Tipos permitidos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX',
            'file.max' => 'El archivo es demasiado grande. Tamaño máximo: 50MB',
            'totalSize.max' => 'El tamaño total del archivo excede el límite de 50MB',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed', ['errors' => $validator->errors()->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $file = $request->file('file');
            $chunkNumber = (int)$request->input('chunkNumber');
            $totalChunks = (int)$request->input('totalChunks');
            $identifier = $request->input('identifier');
            $filename = $request->input('filename');
            $fileType = $request->input('fileType');

            // Validate chunk number
            if ($chunkNumber > $totalChunks) {
                throw new \Exception('Invalid chunk number');
            }

            // Create chunks directory if it doesn't exist
            $chunkDir = 'chunks/' . $identifier;
            $chunkPath = $chunkDir . '/' . $chunkNumber;

            try {
                if (!Storage::exists($chunkDir)) {
                    Storage::makeDirectory($chunkDir, 0755, true);
                }

                // Store the chunk with error handling
                if (!$file->storeAs($chunkDir, $chunkNumber)) {
                    throw new \Exception('Failed to store chunk');
                }

                // If this is the last chunk, combine them
                if ($chunkNumber === $totalChunks) {
                    return $this->combineChunks($identifier, $filename, $request->input('originalName'), $fileType);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Chunk uploaded successfully',
                    'chunkNumber' => $chunkNumber,
                    'totalChunks' => $totalChunks,
                    'fileType' => $fileType
                ]);

            } catch (\Exception $e) {
                // Clean up failed chunk
                if (Storage::exists($chunkPath)) {
                    Storage::delete($chunkPath);
                }
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Upload chunk error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['file'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing chunk: ' . $e->getMessage(),
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Combine uploaded chunks into a single file
     * 
     * @param string $identifier Unique identifier for this upload
     * @param string $filename Final filename
     * @param string $originalName Original filename from the client
     * @param string $fileType MIME type of the file
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    private function combineChunks($identifier, $filename, $originalName, $fileType)
    {
        $chunkDir = 'chunks/' . $identifier;
        $finalDir = 'public/uploads';
        $finalPath = $finalDir . '/' . $filename;
        $finalFullPath = Storage::path($finalPath);
        $chunks = [];
        $finalFile = null;

        try {
            // Validate chunks directory exists
            if (!Storage::exists($chunkDir)) {
                throw new \Exception('Upload directory not found');
            }

            // Get and sort chunks
            $chunks = Storage::files($chunkDir);
            natsort($chunks);

            if (empty($chunks)) {
                throw new \Exception('No chunks found to combine');
            }

            // Ensure the final directory exists
            if (!Storage::exists($finalDir)) {
                Storage::makeDirectory($finalDir, 0755, true);
            }

            // Create or truncate the final file
            $finalFile = fopen($finalFullPath, 'wb');
            if ($finalFile === false) {
                throw new \Exception('Could not create final file');
            }

            // Set file permissions
            chmod($finalFullPath, 0644);

            // Combine all chunks
            foreach ($chunks as $chunk) {
                $chunkPath = Storage::path($chunk);
                $chunkFile = fopen($chunkPath, 'rb');
                
                if ($chunkFile === false) {
                    throw new \Exception("Could not read chunk: {$chunk}");
                }

                try {
                    // Append chunk to final file
                    while (!feof($chunkFile)) {
                        $chunkContent = fread($chunkFile, 8192);
                        if ($chunkContent === false) {
                            throw new \Exception('Error reading chunk content');
                        }
                        if (fwrite($finalFile, $chunkContent) === false) {
                            throw new \Exception('Error writing to final file');
                        }
                    }
                } finally {
                    fclose($chunkFile);
                }
            }

            // Verify the final file was created and has content
            if (!file_exists($finalFullPath) || filesize($finalFullPath) === 0) {
                throw new \Exception('Failed to create final file or file is empty');
            }

            // Clean up chunks
            Storage::deleteDirectory($chunkDir);

            // Get file info for response
            $fileSize = filesize($finalFullPath);
            $mimeType = mime_content_type($finalFullPath) ?: $fileType;

            // Log successful upload
            \Log::info('File upload completed', [
                'original_name' => $originalName,
                'stored_name' => $filename,
                'size' => $fileSize,
                'mime_type' => $mimeType,
                'path' => $finalPath
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'filename' => $filename,
                'original_name' => $originalName,
                'size' => $fileSize,
                'mime_type' => $mimeType,
                'url' => Storage::url($finalPath)
            ]);

        } catch (\Exception $e) {
            // Clean up in case of error
            if (is_resource($finalFile)) {
                fclose($finalFile);
            }
            
            // Delete the final file if it was created
            if (file_exists($finalFullPath)) {
                unlink($finalFullPath);
            }

            // Log the error
            \Log::error('Error combining chunks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'identifier' => $identifier,
                'chunks_found' => count($chunks),
                'final_path' => $finalPath ?? null
            ]);

            throw new \Exception('Failed to combine chunks: ' . $e->getMessage());
        }
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
