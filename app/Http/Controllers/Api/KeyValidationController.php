<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KeyValidationController extends Controller
{
    /**
     * Valida una key de acceso generada por el sistema
     * Este endpoint es usado por los servidores de destino para validar las keys
     * IMPORTANTE: Las keys son de un solo uso y se marcan como usadas inmediatamente
     */
    public function validateKey(Request $request)
    {
        $request->validate([
            'key' => 'required|string|size:51', // ak_ + 48 caracteres
            'url' => 'required|url', // URL requerida para verificación de seguridad
        ]);

        $key = $request->input('key');
        $url = $request->input('url');
        $clientIp = $request->ip();

        try {
            $accessKey = AccessKey::where('key', $key)
                ->with(['certificate.user', 'certificate.services', 'certificate.permissions'])
                ->first();

            if (!$accessKey) {
                Log::warning('Intento de validación con key inexistente', [
                    'key' => substr($key, 0, 10) . '...',
                    'url' => $url,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Key de acceso no encontrada',
                ], 404);
            }

            // Verificar que la key sea válida
            if (!$accessKey->isValid()) {
                $reason = $accessKey->is_used ? 'ya fue utilizada' : 'ha expirado';
                
                Log::warning('Intento de validación con key inválida', [
                    'key_id' => $accessKey->id,
                    'reason' => $reason,
                    'url' => $url,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Key de acceso {$reason}",
                ], 403);
            }

            // Verificar URL - REQUERIDO para seguridad
            if (!$accessKey->target_url) {
                Log::warning('Key sin URL destino asociada', [
                    'key_id' => $accessKey->id,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Key inválida: sin URL destino',
                ], 403);
            }

            $parsedKeyUrl = parse_url($accessKey->target_url);
            $parsedRequestUrl = parse_url($url);
            
            $keyHost = $parsedKeyUrl['host'] ?? null;
            $requestHost = $parsedRequestUrl['host'] ?? null;
            
            // Validación estricta de URL: debe coincidir exactamente el host
            if (!$keyHost || !$requestHost || $keyHost !== $requestHost) {
                Log::warning('Intento de uso de key en URL diferente', [
                    'key_id' => $accessKey->id,
                    'key_url' => $accessKey->target_url,
                    'request_url' => $url,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'La key no es válida para esta URL',
                ], 403);
            }

            // Verificar que el certificado asociado siga siendo válido
            if (!$accessKey->certificate->isValid()) {
                Log::warning('Key asociada a certificado inválido', [
                    'key_id' => $accessKey->id,
                    'certificate_id' => $accessKey->certificate_id,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'El certificado asociado a esta key ya no es válido',
                ], 403);
            }

            // CRÍTICO: Marcar la key como usada ANTES de devolver la respuesta
            // Usar transacción para evitar condiciones de carrera
            $marked = \DB::transaction(function () use ($accessKey) {
                // Refrescar el modelo dentro de la transacción
                $accessKey->refresh();
                
                // Verificar nuevamente que no esté usada (double-check)
                if ($accessKey->is_used) {
                    return false;
                }
                
                return $accessKey->markAsUsed();
            });

            if (!$marked) {
                Log::warning('Intento de reutilización de key detectado', [
                    'key_id' => $accessKey->id,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Esta key ya fue utilizada',
                ], 403);
            }

            Log::info('Key de acceso validada y marcada como usada', [
                'key_id' => $accessKey->id,
                'certificate_id' => $accessKey->certificate_id,
                'service_slug' => $accessKey->service_slug,
                'url' => $url,
                'client_ip' => $clientIp,
                'used_at' => $accessKey->used_at,
            ]);

            return response()->json([
                'success' => true,
                'valid' => true,
                'certificate' => [
                    'id' => $accessKey->certificate->id,
                    'name' => $accessKey->certificate->name,
                    'common_name' => $accessKey->certificate->common_name,
                    'email' => $accessKey->certificate->email, // Email del certificado
                ],
                'user' => [
                    'id' => $accessKey->certificate->user->id,
                    'name' => $accessKey->certificate->user->name,
                    'email' => $accessKey->certificate->email, // Email del certificado, no del usuario
                ],
                'service' => [
                    'slug' => $accessKey->service_slug,
                ],
                'permissions' => $accessKey->certificate->permissions->pluck('slug')->toArray(),
                'expires_at' => $accessKey->expires_at->toIso8601String(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al validar key de acceso', [
                'error' => $e->getMessage(),
                'key' => substr($key, 0, 10) . '...',
                'url' => $url,
                'client_ip' => $clientIp,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la validación: ' . $e->getMessage(),
            ], 500);
        }
    }
}
