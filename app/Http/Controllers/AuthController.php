<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => __('Las credenciales proporcionadas no son correctas.'),
        ]);
    }

    /**
     * Inicio de sesión con certificado X.509 (subiendo archivo)
     */
    public function loginWithCertificate(Request $request)
    {
        $request->validate([
            'certificate_file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    $allowedExtensions = ['pem', 'crt', 'cer', 'p12', 'pfx', 'key'];
                    
                    // Verificar extensión
                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('El archivo debe ser de tipo: ' . implode(', ', $allowedExtensions) . '.');
                    }
                },
            ],
        ]);

        $file = $request->file('certificate_file');
        $certificateContent = file_get_contents($file->getRealPath());
        $extension = $file->getClientOriginalExtension();

        // Parsear el certificado
        $certData = $this->parseCertificate($certificateContent, $extension);
        
        if (!$certData) {
            throw ValidationException::withMessages([
                'certificate_file' => __('El certificado proporcionado no es válido o no se pudo leer.'),
            ]);
        }

        $certInfo = $certData['info'];
        $fingerprint = $certData['fingerprint'];
        $certificatePem = $certData['pem'];

        // Buscar el certificado en BD
        $certificate = $this->findCertificateForLogin($certificatePem, $certInfo, $fingerprint);

        if (!$certificate) {
            throw ValidationException::withMessages([
                'certificate_file' => __('El certificado no existe en el sistema.'),
            ]);
        }

        if (!$certificate->isValid()) {
            $errorMessage = __('El certificado no es válido.');
            if ($certificate->isNotYetValid()) {
                $errorMessage = __('El certificado aún no es válido. Válido desde: ') . $certificate->valid_from->format('d/m/Y H:i');
            } elseif ($certificate->isExpired()) {
                $errorMessage = __('El certificado ha expirado.');
            } elseif (!$certificate->is_active) {
                $errorMessage = __('El certificado está inactivo.');
            }
            
            throw ValidationException::withMessages([
                'certificate_file' => $errorMessage,
            ]);
        }

        if (!$certificate->user) {
            throw ValidationException::withMessages([
                'certificate_file' => __('El certificado no tiene un usuario asociado.'),
            ]);
        }

        Auth::login($certificate->user, true);
        $request->session()->regenerate();

        Log::info('Login con certificado realizado correctamente', [
            'certificate_id' => $certificate->id,
            'user_id' => $certificate->user_id,
            'email' => $certificate->email,
        ]);

        return redirect()->intended('/dashboard');
    }

    /**
     * Buscar certificado para login
     */
    private function findCertificateForLogin(string $certificatePem, array $certInfo, string $fingerprint): ?Certificate
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
        $certificates = Certificate::whereNotNull('x509_certificate')->with('user')->get();
        $normalizedPem = $this->normalizePem($certificatePem);

        foreach ($certificates as $certModel) {
            $storedPem = $this->normalizePem($certModel->x509_certificate);
            if ($storedPem === $normalizedPem) {
                return $certModel;
            }
        }

        // Buscar por fingerprint
        foreach ($certificates as $certModel) {
            try {
                $storedCert = @openssl_x509_read($certModel->x509_certificate);
                if ($storedCert) {
                    $storedFingerprint = openssl_x509_fingerprint($storedCert, 'sha256');
                    if (strtolower($storedFingerprint) === strtolower($fingerprint)) {
                        return $certModel;
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
     * Parsea un certificado desde contenido y extensión
     */
    private function parseCertificate(string $content, string $extension): ?array
    {
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
