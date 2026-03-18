<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Crypt;

class Credential extends Model
{
    public const AUTH_TYPE_FORM = 'form';
    public const AUTH_TYPE_CERTIFICATE_ONLY = 'certificate_only';

    protected $fillable = [
        'user_id',
        'certificate_id',
        'website_name',
        'website_url_pattern',
        'auth_type',
        'username_field_selector',
        'password_field_selector',
        'username',
        'password',
        'submit_button_selector',
        'auto_fill',
        'auto_submit',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'auto_fill' => 'boolean',
        'auto_submit' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'username_value',
        'password_value',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el certificado (asignación directa legacy)
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * Certificados que tienen acceso a esta credencial (pivot certificate_credential)
     */
    public function certificates(): BelongsToMany
    {
        return $this->belongsToMany(Certificate::class, 'certificate_credential');
    }

    /**
     * Obtener el username descifrado
     */
    public function getUsernameAttribute(): string
    {
        if ($this->username_value === null || $this->username_value === '') {
            return '';
        }
        try {
            return Crypt::decryptString($this->username_value);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Obtener el password descifrado
     */
    public function getPasswordAttribute(): string
    {
        if ($this->password_value === null || $this->password_value === '') {
            return '';
        }
        try {
            return Crypt::decryptString($this->password_value);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Establecer el username cifrado
     */
    public function setUsernameAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['username_value'] = null;
            return;
        }
        $this->attributes['username_value'] = Crypt::encryptString($value);
    }

    /**
     * Establecer el password cifrado
     */
    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['password_value'] = null;
            return;
        }
        $this->attributes['password_value'] = Crypt::encryptString($value);
    }

    public function isCertificateOnly(): bool
    {
        return $this->auth_type === self::AUTH_TYPE_CERTIFICATE_ONLY;
    }

    /**
     * Verificar si una URL coincide con el patrón.
     * Patrón: * = cualquier cosa, . = punto literal. Ej: *ionos* coincide con login.ionos.es
     */
    public function matchesUrl(string $url): bool
    {
        $pattern = trim((string) $this->website_url_pattern);
        if ($pattern === '') {
            return false;
        }

        $quoted = preg_quote($pattern, '/');
        $regex = str_replace(['\*', '\.'], ['.*', '\\.'], $quoted);

        return (bool) preg_match('/^' . $regex . '$/i', $url);
    }

    /**
     * Obtener credencial para una URL y certificado/usuario.
     * Si se pasa $allowedCredentialIds (lista no vacía), solo se consideran esas credenciales (acceso restringido por certificado).
     * Si no, se aplica lógica legacy: generales, por usuario o por certificado.
     */
    public static function getForUrl(string $url, ?int $userId = null, ?int $certificateId = null, ?array $allowedCredentialIds = null): ?self
    {
        $all = self::getAllForUrl($url, $userId, $certificateId, $allowedCredentialIds);
        return $all->first();
    }

    /**
     * Obtiene TODAS las credenciales que aplican a una URL, ordenadas por prioridad.
     * - Si $allowedCredentialIds es una lista no vacía, limita a esas credenciales (restricción por certificado).
     * - En modo legacy (sin restricción), prioriza: específica por certificado > específica por usuario > general.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function getAllForUrl(string $url, ?int $userId = null, ?int $certificateId = null, ?array $allowedCredentialIds = null)
    {
        $query = self::where('is_active', true);

        if ($allowedCredentialIds !== null && count($allowedCredentialIds) > 0) {
            $query->whereIn('id', $allowedCredentialIds);
        } else {
            $query->where(function ($q) use ($userId, $certificateId) {
                $q->where(function ($q2) {
                    $q2->whereNull('user_id')->whereNull('certificate_id');
                });
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
                if ($certificateId) {
                    $q->orWhere('certificate_id', $certificateId);
                }
            });
        }

        $candidates = $query->get()->filter(fn ($c) => $c->matchesUrl($url));

        if ($candidates->isEmpty()) {
            return collect();
        }

        if ($allowedCredentialIds !== null && count($allowedCredentialIds) > 0) {
            // Mantener un orden estable por nombre para que el selector sea consistente
            return $candidates->sortBy(fn ($c) => (string) ($c->website_name ?? ''))->values();
        }

        // Priorizar: específica (certificado o usuario) antes que general
        return $candidates
            ->sortByDesc(function ($c) use ($userId, $certificateId) {
                $score = 0;
                if ($certificateId && $c->certificate_id == $certificateId) {
                    $score += 2;
                }
                if ($userId && $c->user_id == $userId) {
                    $score += 1;
                }
                return $score;
            })
            ->values();
    }
}
