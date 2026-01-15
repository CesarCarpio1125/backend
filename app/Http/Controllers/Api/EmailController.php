<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendInquiry;

class EmailController extends Controller
{
    public function sendInquiry(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'from' => 'required|email|max:255',
                'to' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
                'attachment_path' => 'required|string',
                'original_name' => 'required|string',
            ], [
                'attachment_path.required' => 'La ruta del archivo adjunto es obligatoria.',
                'original_name.required' => 'El nombre original del archivo es obligatorio.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            $attachmentPath = null;

            // Verificar que el archivo existe
            $fullPath = storage_path('app/public/' . ltrim($data['attachment_path'], '/'));
            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo adjunto no se encontró en el servidor.'
                ], 404);
            }

            $attachmentPath = $fullPath;
            $originalName = $data['original_name'] ?? 'documento.pdf';

            try {
                // Enviar el correo
                Mail::to($data['to'])
                    ->send(new SendInquiry($data, $attachmentPath, $originalName));

                // Eliminar el archivo temporal después de enviar el correo
                if (file_exists($attachmentPath)) {
                    unlink($attachmentPath);
                }

                // Registrar el envío exitoso
                \Log::info('Correo enviado correctamente', [
                    'to' => $data['to'],
                    'subject' => $data['subject'],
                    'attachment' => $originalName
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Correo enviado correctamente',
                    'data' => [
                        'to' => $data['to'],
                        'subject' => $data['subject'],
                        'attachment' => $originalName
                    ]
                ], 200);

            } catch (\Exception $e) {
                // Registrar el error
                \Log::error('Error al enviar el correo: ' . $e->getMessage(), [
                    'to' => $data['to'],
                    'error' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar el correo: ' . $e->getMessage(),
                    'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error al procesar la solicitud'
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Error al enviar correo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $e->getMessage()
            ], 500);
        }
    }
}
