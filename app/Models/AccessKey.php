<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessKey extends Model
{
    protected $fillable = [
        'certificate_id',
        'key',
        'service_slug',
        'target_url',
        'client_ip',
        'expires_at',
        'is_used',
        'used_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'is_used' => 'boolean',
        'metadata' => 'array',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function isValid(): bool
    {
        if ($this->is_used) {
            return false;
        }

        return now()->lte($this->expires_at);
    }

    public function markAsUsed(): bool
    {
        // Usar update con condición para evitar condiciones de carrera
        // Solo actualiza si aún no está usada
        $updated = static::where('id', $this->id)
            ->where('is_used', false)
            ->update([
                'is_used' => true,
                'used_at' => now(),
            ]);

        // Refrescar el modelo
        $this->refresh();

        return $updated > 0;
    }
}
