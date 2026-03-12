<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CertificateValidatorController extends Controller
{
    public function index()
    {
        return view('validator.index');
    }

    public function validate(Request $request)
    {
        $request->validate([
            'certificate_file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    $allowedExtensions = ['pem', 'crt', 'cer', 'p12', 'pfx', 'key'];
                    $mimeType = $value->getMimeType();
                    
                    // Verificar extensión
                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('El archivo debe ser de tipo: ' . implode(', ', $allowedExtensions) . '.');
                    }
                    
                    // Verificar contenido para archivos PEM (pueden tener varios MIME types)
                    if (in_array($extension, ['pem', 'crt', 'cer', 'key'])) {
                        $content = file_get_contents($value->getRealPath());
                        if (strpos($content, '-----BEGIN') === false && strpos($content, '----- CERTIFICATE') === false) {
                            // No es un archivo PEM válido, pero lo intentaremos parsear de todas formas
                        }
                    }
                },
            ],
            'certificate_key' => 'nullable|string', // Opcional: validar por clave también
        ]);

        $file = $request->file('certificate_file');
        $certificateContent = file_get_contents($file->getRealPath());
        $certificateKey = $request->input('certificate_key');

        $result = [
            'valid' => false,
            'certificate' => null,
            'user' => null,
            'services' => [],
            'permissions' => [],
            'errors' => [],
            'certificate_info' => null,
        ];

        try {
            // Intentar parsear el certificado
            $certData = $this->parseCertificate($certificateContent, $file->getClientOriginalExtension());
            
            if (!$certData) {
                $result['errors'][] = 'No se pudo parsear el certificado. Verifica que el archivo sea válido.';
                return view('validator.result', compact('result'));
            }

            $result['certificate_info'] = $certData['info'];

            // Buscar el certificado en la base de datos
            $certificate = null;

            // Buscar por clave del certificado si se proporciona
            if ($certificateKey) {
                $certificate = Certificate::where('certificate_key', $certificateKey)
                    ->with(['user', 'services', 'permissions'])
                    ->first();
            }

            // Si no se encontró por clave, buscar por información del certificado X.509
            if (!$certificate && isset($certData['info']['subject']['CN'])) {
                $certificate = Certificate::where('common_name', $certData['info']['subject']['CN'])
                    ->with(['user', 'services', 'permissions'])
                    ->first();
            }

            // Si aún no se encuentra, buscar por fingerprint o comparando el contenido del certificado
            if (!$certificate && isset($certData['pem'])) {
                // Buscar comparando el contenido del certificado PEM
                $certificates = Certificate::whereNotNull('x509_certificate')
                    ->get();
                
                foreach ($certificates as $cert) {
                    try {
                        // Comparar directamente el contenido PEM (normalizado)
                        $storedPem = trim($cert->x509_certificate);
                        $uploadedPem = trim($certData['pem']);
                        
                        // Normalizar saltos de línea
                        $storedPem = preg_replace('/\r\n|\r|\n/', "\n", $storedPem);
                        $uploadedPem = preg_replace('/\r\n|\r|\n/', "\n", $uploadedPem);
                        
                        if ($storedPem === $uploadedPem) {
                            $certificate = $cert->load(['user', 'services', 'permissions']);
                            break;
                        }
                        
                        // También intentar por fingerprint si está disponible
                        if (isset($certData['fingerprint'])) {
                            $storedCertInfo = @openssl_x509_parse($cert->x509_certificate);
                            if ($storedCertInfo && isset($storedCertInfo['fingerprint']) && 
                                strtolower($storedCertInfo['fingerprint']) === strtolower($certData['fingerprint'])) {
                                $certificate = $cert->load(['user', 'services', 'permissions']);
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            if ($certificate) {
                $result['valid'] = $certificate->isValid();
                $result['certificate'] = $certificate;
                $result['user'] = $certificate->user;
                $result['services'] = $certificate->services;
                $result['permissions'] = $certificate->permissions;

                if (!$result['valid']) {
                    if ($certificate->isNotYetValid()) {
                        $result['errors'][] = 'El certificado aún no es válido. Válido desde: ' . $certificate->valid_from->format('d/m/Y H:i');
                    } elseif ($certificate->isExpired()) {
                        $result['errors'][] = 'El certificado ha expirado. Expiró el: ' . ($certificate->valid_until ? $certificate->valid_until->format('d/m/Y H:i') : 'N/A');
                    } elseif (!$certificate->is_active) {
                        $result['errors'][] = 'El certificado está inactivo.';
                    }
                }
            } else {
                $result['errors'][] = 'El certificado no se encontró en la base de datos.';
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Error al procesar el certificado: ' . $e->getMessage();
        }

        return view('validator.result', compact('result'));
    }

    private function parseCertificate(string $content, string $extension): ?array
    {
        $certData = null;

        try {
            if (in_array(strtolower($extension), ['p12', 'pfx'])) {
                // Para archivos PKCS#12, necesitaríamos la contraseña
                // Por ahora, intentamos extraer el certificado si es posible
                return null; // Requiere contraseña, mejor manejar solo PEM/CRT
            }

            // Intentar parsear como PEM
            $cert = @openssl_x509_read($content);
            
            if (!$cert) {
                // Intentar extraer el certificado del contenido si incluye clave privada
                $parts = explode('-----BEGIN CERTIFICATE-----', $content);
                if (count($parts) > 1) {
                    $certContent = '-----BEGIN CERTIFICATE-----' . $parts[1];
                    $cert = @openssl_x509_read($certContent);
                }
            }

            if ($cert) {
                $info = openssl_x509_parse($cert);
                $fingerprint = openssl_x509_fingerprint($cert, 'sha256');
                
                openssl_x509_export($cert, $pem);
                
                return [
                    'info' => $info,
                    'fingerprint' => $fingerprint,
                    'pem' => $pem,
                ];
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
