<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateUsageLog;
use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CredentialApiController extends Controller
{
    /**
     * Obtiene credenciales para una URL específica usando un certificado
     */
    public function getCredentials(Request $request)
    {
        $request->validate([
            'certificate' => 'required|string', // Certificado en formato PEM
            'url' => 'required|url', // URL actual donde se necesita la credencial
        ]);

        $certificatePem = $request->input('certificate');
        $currentUrl = $request->input('url');
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
                Log::warning('Intento de obtener credenciales con certificado no encontrado', [
                    'url' => $currentUrl,
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
                Log::warning('Intento de obtener credenciales con certificado inválido', [
                    'certificate_id' => $certificate->id,
                    'url' => $currentUrl,
                    'client_ip' => $clientIp,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Certificado inválido o expirado',
                ], 403);
            }

            // Buscar credenciales para esta URL (incluye credenciales generales sin usuario/certificado)
            $credential = Credential::getForUrl(
                $currentUrl,
                $certificate->user_id,
                $certificate->id
            );

            if (!$credential) {
                Log::debug('HawCert: No credential matched URL', [
                    'url' => $currentUrl,
                    'certificate_id' => $certificate->id,
                    'user_id' => $certificate->user_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron credenciales para esta URL',
                    'credential' => null,
                ], 404);
            }

            $isCertificateOnly = $credential->isCertificateOnly();

            Log::info('✅ Credenciales obtenidas exitosamente', [
                'credential_id' => $credential->id,
                'website_name' => $credential->website_name,
                'certificate_only' => $isCertificateOnly,
                'url' => $currentUrl,
                'certificate_id' => $certificate->id,
            ]);

            // Registrar en logs solo cuando el usuario pulsó "Rellenar ahora" en la extensión, no por navegación automática
            if ($request->boolean('manual')) {
                CertificateUsageLog::logUsage(
                    $certificate->id,
                    'credentials',
                    $credential->website_name . ' (' . $currentUrl . ')',
                    $clientIp,
                    $request->userAgent()
                );
            }

            $payload = [
                'id' => $credential->id,
                'website_name' => $credential->website_name,
                'certificate_only' => $isCertificateOnly,
            ];

            if (!$isCertificateOnly) {
                $payload['username_field_selector'] = $credential->username_field_selector;
                $payload['password_field_selector'] = $credential->password_field_selector;
                $payload['submit_button_selector'] = $credential->submit_button_selector;
                $payload['username'] = $credential->username;
                $payload['password'] = $credential->password;
                $payload['auto_fill'] = $credential->auto_fill;
                $payload['auto_submit'] = true;
            }

            return response()->json([
                'success' => true,
                'credential' => $payload,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener credenciales', [
                'error' => $e->getMessage(),
                'url' => $currentUrl,
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
                ->with(['user'])
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
                return $cert->load(['user']);
            }
        }

        // Buscar por fingerprint
        foreach ($certificates as $cert) {
            try {
                $storedCert = @openssl_x509_read($cert->x509_certificate);
                if ($storedCert) {
                    $storedFingerprint = openssl_x509_fingerprint($storedCert, 'sha256');
                    if (strtolower($storedFingerprint) === strtolower($fingerprint)) {
                        return $cert->load(['user']);
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
}
