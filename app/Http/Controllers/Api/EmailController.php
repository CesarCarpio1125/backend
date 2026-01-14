<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendInquiry;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function sendInquiry(Request $request)
    {
        // Increase PHP and server limits for file uploads
        ini_set('upload_max_filesize', '20M');
        ini_set('post_max_size', '25M');
        ini_set('max_execution_time', 300); // 5 minutes
        set_time_limit(300);
        
        $validator = Validator::make($request->all(), [
            'from' => 'required|email|max:255',
            'to' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachment' => 'required|file|mimes:pdf|max:20480', // Máximo 20MB
        ], [
            'attachment.required' => 'El archivo adjunto es obligatorio.',
            'attachment.file' => 'El archivo adjunto no es válido.',
            'attachment.mimes' => 'El archivo debe ser de tipo PDF.',
            'attachment.max' => 'El archivo no debe ser mayor a 20MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $attachmentPath = null;
        
        try {
            $data = $request->all();
            
            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                
                // Generate a unique filename
                $fileName = 'informe_' . time() . '_' . uniqid() . '.pdf';
                
                // Store the file in the storage/app/public/attachments directory
                $path = $file->storeAs('attachments', $fileName, 'public');
                
                if (!$path) {
                    throw new \Exception('No se pudo guardar el archivo adjunto.');
                }
                
                $attachmentPath = storage_path('app/public/' . $path);
                
                // Verify the file exists and is readable
                if (!file_exists($attachmentPath) || !is_readable($attachmentPath)) {
                    throw new \Exception('El archivo adjunto no se pudo leer correctamente.');
                }
                
                // Verify file size again (double-check)
                $fileSize = filesize($attachmentPath);
                $maxSize = 20 * 1024 * 1024; // 20MB
                
                if ($fileSize > $maxSize) {
                    throw new \Exception('El archivo es demasiado grande. Tamaño máximo permitido: 20MB');
                }
            }
            
            // Send the email
            Mail::to($data['to'])
                ->send(new SendInquiry($data, $attachmentPath));
            
            return response()->json([
                'success' => true,
                'message' => 'Correo enviado correctamente.'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al enviar el correo: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el mensaje: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        } finally {
            // Clean up: delete the temporary file if it exists
            if ($attachmentPath && file_exists($attachmentPath)) {
                try {
                    unlink($attachmentPath);
                } catch (\Exception $e) {
                    \Log::error('No se pudo eliminar el archivo temporal: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
