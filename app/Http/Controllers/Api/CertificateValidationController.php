<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CertificateValidationController extends Controller
{
    public function validateCertificate(Request $request)
    {
        $request->validate([
            'certificate_key' => 'required|string',
            'service_slug' => 'required|string',
        ]);

        $certificateKey = $request->input('certificate_key');
        $serviceSlug = $request->input('service_slug');
        $origin = $request->header('Origin') ?? $request->ip();

        $certificate = Certificate::where('certificate_key', $certificateKey)
            ->with(['services', 'permissions', 'user'])
            ->first();

        if (!$certificate) {
            Log::warning('Intento de validación con certificado inexistente', [
                'certificate_key' => $certificateKey,
                'service_slug' => $serviceSlug,
                'origin' => $origin,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Certificado no encontrado',
            ], 404);
        }

        if (!$certificate->isValid()) {
            Log::warning('Intento de validación con certificado inválido', [
                'certificate_id' => $certificate->id,
                'certificate_key' => $certificateKey,
                'service_slug' => $serviceSlug,
                'is_active' => $certificate->is_active,
                'valid_from' => $certificate->valid_from,
                'valid_until' => $certificate->valid_until,
                'origin' => $origin,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Certificado inválido o expirado',
            ], 403);
        }

        if (!$certificate->hasService($serviceSlug)) {
            Log::warning('Intento de acceso a servicio no autorizado', [
                'certificate_id' => $certificate->id,
                'certificate_key' => $certificateKey,
                'service_slug' => $serviceSlug,
                'origin' => $origin,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'El certificado no tiene acceso a este servicio',
            ], 403);
        }

        // Generar token de acceso temporal
        $accessToken = bin2hex(random_bytes(32));
        $expiresAt = now()->addHours(24);

        // Guardar el token en cache o base de datos (simplificado aquí)
        cache()->put("access_token:{$accessToken}", [
            'certificate_id' => $certificate->id,
            'user_id' => $certificate->user_id,
            'service_slug' => $serviceSlug,
            'permissions' => $certificate->permissions->pluck('slug')->toArray(),
        ], $expiresAt);

        Log::info('✅ Certificado validado exitosamente', [
            'user_id' => $certificate->user_id,
            'user_email' => $certificate->email,
            'access_token' => substr($accessToken, 0, 20) . '...',
        ]);

        return response()->json([
            'success' => true,
            'access_token' => $accessToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => [
                'id' => $certificate->user->id,
                'name' => $certificate->user->name,
                'email' => $certificate->email, // Email del certificado, no del usuario
            ],
            'permissions' => $certificate->permissions->pluck('slug')->toArray(),
            'certificate' => [
                'id' => $certificate->id,
                'name' => $certificate->name,
                'email' => $certificate->email,
                'valid_until' => $certificate->valid_until ? $certificate->valid_until->toIso8601String() : null,
                'never_expires' => $certificate->never_expires,
            ],
        ], 200);
    }
}
