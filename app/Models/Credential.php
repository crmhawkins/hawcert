<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Credential extends Model
{
    protected $fillable = [
        'user_id',
        'certificate_id',
        'website_name',
        'website_url_pattern',
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
     * Relación con el certificado
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * Obtener el username descifrado
     */
    public function getUsernameAttribute(): string
    {
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
        $this->attributes['username_value'] = Crypt::encryptString($value);
    }

    /**
     * Establecer el password cifrado
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password_value'] = Crypt::encryptString($value);
    }

    /**
     * Verificar si una URL coincide con el patrón
     */
    public function matchesUrl(string $url): bool
    {
        $pattern = $this->website_url_pattern;
        
        // Convertir patrón wildcard a regex
        $regex = str_replace(
            ['*', '.'],
            ['.*', '\.'],
            $pattern
        );
        
        return (bool) preg_match('/^' . $regex . '$/i', $url);
    }

    /**
     * Obtener credenciales para una URL y certificado/usuario.
     * Incluye credenciales "generales" (sin usuario ni certificado), que aplican a cualquier certificado.
     * Prioridad: credencial específica (usuario/certificado) > credencial general.
     */
    public static function getForUrl(string $url, ?int $userId = null, ?int $certificateId = null): ?self
    {
        $candidates = self::where('is_active', true)
            ->where(function ($q) use ($userId, $certificateId) {
                $q->where(function ($q2) {
                    $q2->whereNull('user_id')->whereNull('certificate_id');
                });
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
                if ($certificateId) {
                    $q->orWhere('certificate_id', $certificateId);
                }
            })
            ->get()
            ->filter(fn ($c) => $c->matchesUrl($url));

        if ($candidates->isEmpty()) {
            return null;
        }

        // Priorizar: específica (certificado o usuario) antes que general
        $sorted = $candidates->sortByDesc(function ($c) use ($userId, $certificateId) {
            $score = 0;
            if ($certificateId && $c->certificate_id == $certificateId) {
                $score += 2;
            }
            if ($userId && $c->user_id == $userId) {
                $score += 1;
            }
            return $score;
        });

        return $sorted->first();
    }
}
