<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessKey;
use App\Models\Certificate;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AccessValidationController extends Controller
{
    /**
     * Valida el certificado y genera una key de acceso si tiene permisos para la URL/servicio
     */
    public function validateAccess(Request $request)
    {
        $request->validate([
            'certificate' => 'required|string', // Certificado en formato PEM
            'url' => 'required|url', // URL del servicio destino
            'service_slug' => 'nullable|string', // Slug del servicio (opcional, se puede inferir de la URL)
        ]);

        $certificatePem = $request->input('certificate');
        $targetUrl = $request->input('url');
        $serviceSlug = $request->input('service_slug');
        $clientIp = $request->ip();

        try {
            // Parsear el certificado
            $cert = @openssl_x509_read($certificatePem);
            if (!$cert) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificado inválido o no se pudo parsear',
                ], 400);
            }

            $certInfo = openssl_x509_parse($cert);
            $fingerprint = openssl_x509_fingerprint($cert, 'sha256');

            // Buscar el certificado en la base de datos
            $certificate = $this->findCertificate($certificatePem, $certInfo, $fingerprint);

            if (!$certificate) {
                Log::warning('Intento de acceso con certificado no encontrado', [
                    'url' => $targetUrl,
                    'client_ip' => $clientIp,
                    'cn' => $certInfo['subject']['CN'] ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Certificado no encontrado en el sistema',
                ], 404);
            }

            // Verificar que el certificado sea válido
            if (!$certificate->isValid()) {
                Log::warning('Intento de acceso con certificado inválido', [
                    'certificate_id' => $certificate->id,
                    'url' => $targetUrl,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Certificado inválido o expirado',
                ], 403);
            }

            // Determinar el servicio desde la URL si no se proporcionó
            if (!$serviceSlug) {
                $serviceSlug = $this->determineServiceFromUrl($targetUrl);
            }

            if (!$serviceSlug) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo determinar el servicio desde la URL',
                ], 400);
            }

            // Verificar que el certificado tenga acceso al servicio
            if (!$certificate->hasService($serviceSlug)) {
                Log::warning('Intento de acceso a servicio no autorizado', [
                    'certificate_id' => $certificate->id,
                    'service_slug' => $serviceSlug,
                    'url' => $targetUrl,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'El certificado no tiene acceso a este servicio',
                ], 403);
            }

            if ($serviceSlug === Certificate::HAWCERT_SERVICE_SLUG && !$certificate->can_access_hawcert) {
                Log::warning('Intento de acceso a HawCert sin permiso', [
                    'certificate_id' => $certificate->id,
                    'url' => $targetUrl,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Este certificado no tiene acceso a la plataforma HawCert',
                ], 403);
            }

            // Verificar que el servicio exista y esté activo
            $service = Service::where('slug', $serviceSlug)->where('is_active', true)->first();
            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio no encontrado o inactivo',
                ], 404);
            }

            // Generar key de acceso temporal
            $accessKey = $this->generateAccessKey($certificate, $serviceSlug, $targetUrl, $clientIp);

            Log::info('✅ Certificado validado exitosamente', [
                'user_id' => $certificate->user_id,
                'user_email' => $certificate->email,
                'access_key' => $accessKey->key,
            ]);

            return response()->json([
                'success' => true,
                'access_key' => $accessKey->key,
                'expires_at' => $accessKey->expires_at->toIso8601String(),
                'service' => [
                    'name' => $service->name,
                    'slug' => $service->slug,
                ],
                'user' => [
                    'id' => $certificate->user->id,
                    'name' => $certificate->user->name,
                    'email' => $certificate->email, // Email del certificado, no del usuario
                ],
                'certificate' => [
                    'id' => $certificate->id,
                    'name' => $certificate->name,
                    'email' => $certificate->email,
                ],
                'permissions' => $certificate->permissions->pluck('slug')->toArray(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al validar acceso', [
                'error' => $e->getMessage(),
                'url' => $targetUrl,
                'client_ip' => $clientIp,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca el certificado en la base de datos
     */
    private function findCertificate(string $certificatePem, array $certInfo, string $fingerprint): ?Certificate
    {
        // Buscar por Common Name
        if (isset($certInfo['subject']['CN'])) {
            $certificate = Certificate::where('common_name', $certInfo['subject']['CN'])
                ->with(['user', 'services', 'permissions'])
                ->first();
            
            if ($certificate) {
                return $certificate;
            }
        }

        // Buscar comparando el contenido PEM
        $certificates = Certificate::whereNotNull('x509_certificate')->get();
        $normalizedPem = $this->normalizePem($certificatePem);
        
        foreach ($certificates as $cert) {
            $storedPem = $this->normalizePem($cert->x509_certificate);
            if ($storedPem === $normalizedPem) {
                return $cert->load(['user', 'services', 'permissions']);
            }
        }

        // Buscar por fingerprint
        foreach ($certificates as $cert) {
            try {
                $storedCert = @openssl_x509_read($cert->x509_certificate);
                if ($storedCert) {
                    $storedFingerprint = openssl_x509_fingerprint($storedCert, 'sha256');
                    if (strtolower($storedFingerprint) === strtolower($fingerprint)) {
                        return $cert->load(['user', 'services', 'permissions']);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Normaliza un certificado PEM para comparación
     */
    private function normalizePem(string $pem): string
    {
        return trim(preg_replace('/\r\n|\r|\n/', "\n", $pem));
    }

    /**
     * Determina el servicio desde la URL
     */
    private function determineServiceFromUrl(string $url): ?string
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? null;

        if (!$host) {
            return null;
        }

        // Buscar servicios que coincidan con el host o endpoint
        $services = Service::where('is_active', true)->get();
        
        foreach ($services as $service) {
            if ($service->endpoint) {
                $serviceUrl = parse_url($service->endpoint);
                $serviceHost = $serviceUrl['host'] ?? null;
                
                if ($serviceHost && ($serviceHost === $host || str_ends_with($host, '.' . $serviceHost))) {
                    return $service->slug;
                }
            }
            
            // También verificar por slug si coincide con el dominio
            if (str_contains($host, $service->slug)) {
                return $service->slug;
            }
        }

        return null;
    }

    /**
     * Genera una key de acceso temporal
     */
    private function generateAccessKey(Certificate $certificate, string $serviceSlug, string $targetUrl, ?string $clientIp): AccessKey
    {
        $key = 'ak_' . Str::random(48); // Key de acceso única
        $expiresAt = now()->addHours(24); // Válida por 24 horas

        return AccessKey::create([
            'certificate_id' => $certificate->id,
            'key' => $key,
            'service_slug' => $serviceSlug,
            'target_url' => $targetUrl,
            'client_ip' => $clientIp,
            'expires_at' => $expiresAt,
            'is_used' => false,
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'user_id' => $certificate->user_id,
            ],
        ]);
    }
}
