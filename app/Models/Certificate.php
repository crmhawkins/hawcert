<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'certificate_key',
        'x509_certificate',
        'private_key',
        'common_name',
        'organization',
        'organizational_unit',
        'email',
        'name',
        'description',
        'valid_from',
        'valid_until',
        'never_expires',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'never_expires' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function accessKeys()
    {
        return $this->hasMany(AccessKey::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        
        // Verificar que ya haya comenzado
        if ($now->lt($this->valid_from)) {
            return false;
        }
        
        // Si nunca expira, solo verificar que ya haya comenzado
        if ($this->never_expires) {
            return true;
        }

        // Si tiene fecha de expiración, verificar que no haya expirado
        return $this->valid_until ? $now->lte($this->valid_until) : true;
    }

    public function isExpired(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        
        // Si aún no ha comenzado, no está expirado
        if ($now->lt($this->valid_from)) {
            return false;
        }
        
        // Si nunca expira, nunca está expirado
        if ($this->never_expires) {
            return false;
        }

        // Está expirado si pasó la fecha de expiración
        return $this->valid_until ? $now->gt($this->valid_until) : false;
    }

    public function isNotYetValid(): bool
    {
        return now()->lt($this->valid_from);
    }

    public function hasService(string $serviceSlug): bool
    {
        return $this->services()->where('slug', $serviceSlug)->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }
}
