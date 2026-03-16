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
     * Inicio de sesión con certificado X.509 (pegando el PEM en el formulario)
     */
    public function loginWithCertificate(Request $request)
    {
        $request->validate([
            'certificate' => 'required|string',
        ]);

        $certificatePem = $request->input('certificate');

        $cert = @openssl_x509_read($certificatePem);
        if (!$cert) {
            throw ValidationException::withMessages([
                'certificate' => __('El certificado proporcionado no es válido o no se pudo leer.'),
            ]);
        }

        $certInfo = openssl_x509_parse($cert);
        $fingerprint = openssl_x509_fingerprint($cert, 'sha256');

        // Buscar el certificado en BD reutilizando la misma lógica que la API
        $certificate = $this->findCertificateForLogin($certificatePem, $certInfo, $fingerprint);

        if (!$certificate) {
            throw ValidationException::withMessages([
                'certificate' => __('El certificado no existe en el sistema.'),
            ]);
        }

        if (!$certificate->isValid()) {
            throw ValidationException::withMessages([
                'certificate' => __('El certificado no es válido o ha expirado.'),
            ]);
        }

        if (!$certificate->user) {
            throw ValidationException::withMessages([
                'certificate' => __('El certificado no tiene un usuario asociado.'),
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
