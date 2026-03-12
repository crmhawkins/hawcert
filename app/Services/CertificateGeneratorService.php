<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CertificateGeneratorService
{
    /**
     * Genera un certificado X.509 real usando OpenSSL
     */
    public function generateX509Certificate(array $data): array
    {
        if (!extension_loaded('openssl')) {
            throw new \Exception('La extensión OpenSSL no está disponible en este servidor.');
        }

        $commonName = $data['common_name'] ?? $data['name'] ?? 'cert_' . Str::random(16);
        $organization = $data['organization'] ?? 'HawCert';
        $organizationalUnit = $data['organizational_unit'] ?? 'IT Department';
        $email = $data['email'] ?? 'cert@hawcert.local';
        $validFrom = $data['valid_from'] ?? now();
        $validUntil = $data['valid_until'] ?? now()->addYears(1);
        $neverExpires = $data['never_expires'] ?? false;

        // Configuración del certificado
        $dn = [
            "countryName" => "ES",
            "stateOrProvinceName" => "España",
            "localityName" => "Madrid",
            "organizationName" => $organization,
            "organizationalUnitName" => $organizationalUnit,
            "commonName" => $commonName,
            "emailAddress" => $email,
        ];

        // Configurar OpenSSL - intentar con y sin configuración explícita
        $configPath = $this->getOpenSSLConfigPath();
        
        $config = [
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
            "digest_alg" => "sha256",
        ];
        
        // Añadir configuración solo si existe
        if ($configPath) {
            $config["config"] = $configPath;
        }

        // Generar clave privada
        $privateKey = @openssl_pkey_new($config);

        if (!$privateKey) {
            // Intentar sin configuración si falla con configuración
            if (isset($config["config"])) {
                unset($config["config"]);
                $privateKey = @openssl_pkey_new($config);
            }
            
            if (!$privateKey) {
                $error = '';
                while (($msg = openssl_error_string()) !== false) {
                    $error .= $msg . "\n";
                }
                throw new \Exception('Error al generar la clave privada. ' . ($error ?: 'Verifica que OpenSSL esté correctamente instalado.'));
            }
        }

        // Exportar clave privada
        openssl_pkey_export($privateKey, $privateKeyPem);

        // Obtener clave pública
        $publicKey = openssl_pkey_get_details($privateKey);
        if (!$publicKey) {
            throw new \Exception('Error al obtener la clave pública');
        }

        // Crear certificado
        $csrConfig = [
            'digest_alg' => 'sha256',
            'x509_extensions' => 'v3_ca',
        ];
        
        $configPath = $this->getOpenSSLConfigPath();
        if ($configPath) {
            $csrConfig['config'] = $configPath;
        }
        
        $csr = @openssl_csr_new($dn, $privateKey, $csrConfig);

        if (!$csr) {
            $error = '';
            while (($msg = openssl_error_string()) !== false) {
                $error .= $msg . "\n";
            }
            throw new \Exception('Error al crear la solicitud de certificado: ' . ($error ?: 'Error desconocido'));
        }

        // Configurar extensiones del certificado
        $x509Extensions = [
            'keyUsage' => ['keyEncipherment', 'digitalSignature'],
            'extendedKeyUsage' => ['clientAuth', 'serverAuth'],
            'subjectAltName' => [
                'DNS.1' => $commonName,
                'DNS.2' => '*.hawcert.local',
            ],
        ];

        // Calcular días de validez
        $days = $neverExpires ? 3650 : max(1, (int)$validFrom->diffInDays($validUntil));
        
        // Generar certificado X.509
        $signConfig = [
            'digest_alg' => 'sha256',
        ];
        
        $configPath = $this->getOpenSSLConfigPath();
        if ($configPath) {
            $signConfig['config'] = $configPath;
        }
        
        $certificate = @openssl_csr_sign(
            $csr,
            null, // Self-signed (sin CA)
            $privateKey,
            $days,
            $signConfig,
            time() // Serial number basado en timestamp
        );

        if (!$certificate) {
            $error = '';
            while (($msg = openssl_error_string()) !== false) {
                $error .= $msg . "\n";
            }
            throw new \Exception('Error al firmar el certificado: ' . ($error ?: 'Error desconocido'));
        }

        // Exportar certificado en formato PEM
        openssl_x509_export($certificate, $certificatePem);

        // Obtener información del certificado
        $certInfo = openssl_x509_parse($certificate);

        return [
            'x509_certificate' => $certificatePem,
            'private_key' => Crypt::encryptString($privateKeyPem), // Encriptar clave privada
            'common_name' => $commonName,
            'organization' => $organization,
            'organizational_unit' => $organizationalUnit,
            'serial_number' => $certInfo['serialNumber'] ?? null,
            'fingerprint' => $certInfo['fingerprint'] ?? null,
        ];
    }

    /**
     * Genera un archivo PKCS#12 (.p12/.pfx) para descarga
     */
    public function generateP12(string $certificatePem, string $privateKeyPem, string $password = null): string
    {
        $password = $password ?? Str::random(16);
        
        // Crear archivo temporal para el certificado
        $certFile = tmpfile();
        $certPath = stream_get_meta_data($certFile)['uri'];
        file_put_contents($certPath, $certificatePem);

        // Crear archivo temporal para la clave privada
        $keyFile = tmpfile();
        $keyPath = stream_get_meta_data($keyFile)['uri'];
        file_put_contents($keyPath, $privateKeyPem);

        // Crear archivo temporal para el P12
        $p12File = tmpfile();
        $p12Path = stream_get_meta_data($p12File)['uri'];

        // Convertir a PKCS#12 usando OpenSSL
        $command = sprintf(
            'openssl pkcs12 -export -out %s -inkey %s -in %s -passout pass:%s -nodes',
            escapeshellarg($p12Path),
            escapeshellarg($keyPath),
            escapeshellarg($certPath),
            escapeshellarg($password)
        );

        exec($command, $output, $returnVar);

        // Limpiar archivos temporales
        fclose($certFile);
        fclose($keyFile);

        if ($returnVar !== 0) {
            throw new \Exception('Error al generar archivo P12');
        }

        $p12Content = file_get_contents($p12Path);
        fclose($p12File);

        return $p12Content;
    }

    /**
     * Obtiene la ruta del archivo de configuración de OpenSSL
     */
    private function getOpenSSLConfigPath(): ?string
    {
        // En Windows, buscar openssl.cnf en ubicaciones comunes
        if (PHP_OS_FAMILY === 'Windows') {
            $possiblePaths = [
                'C:/Program Files/OpenSSL-Win64/bin/openssl.cfg',
                'C:/OpenSSL-Win64/bin/openssl.cfg',
                'C:/Program Files (x86)/OpenSSL-Win32/bin/openssl.cfg',
                'C:/OpenSSL-Win32/bin/openssl.cfg',
                getenv('OPENSSL_CONF'),
            ];

            foreach ($possiblePaths as $path) {
                if ($path && file_exists($path)) {
                    return $path;
                }
            }

            // Si no se encuentra, crear un archivo de configuración temporal mínimo
            return $this->createTempOpenSSLConfig();
        }

        // En Linux/Unix, usar la configuración del sistema
        $configPath = getenv('OPENSSL_CONF');
        if ($configPath && file_exists($configPath)) {
            return $configPath;
        }

        // Intentar ubicaciones comunes en Linux
        $linuxPaths = [
            '/etc/ssl/openssl.cnf',
            '/usr/lib/ssl/openssl.cnf',
            '/usr/local/ssl/openssl.cnf',
        ];

        foreach ($linuxPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Si no se encuentra, crear configuración temporal mínima
        return $this->createTempOpenSSLConfig();
    }

    /**
     * Crea un archivo de configuración temporal mínimo para OpenSSL
     */
    private function createTempOpenSSLConfig(): string
    {
        $configContent = <<<'CONFIG'
[req]
distinguished_name = req_distinguished_name
req_extensions = v3_req
prompt = no

[req_distinguished_name]
C = ES
ST = España
L = Madrid
O = HawCert
OU = IT Department

[v3_req]
keyUsage = keyEncipherment, digitalSignature
extendedKeyUsage = serverAuth, clientAuth
basicConstraints = CA:FALSE

[v3_ca]
keyUsage = keyEncipherment, digitalSignature
extendedKeyUsage = serverAuth, clientAuth
basicConstraints = CA:FALSE
CONFIG;

        $tempFile = tempnam(sys_get_temp_dir(), 'openssl_');
        file_put_contents($tempFile, $configContent);
        
        // Registrar para limpiar al finalizar
        register_shutdown_function(function() use ($tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        });

        return $tempFile;
    }
}
