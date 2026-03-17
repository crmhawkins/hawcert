<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateUsageLog extends Model
{
    protected $fillable = [
        'certificate_id',
        'event_type',
        'site',
        'ip_address',
        'user_agent',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public static function logUsage(int $certificateId, string $eventType, ?string $site = null, ?string $ip = null, ?string $userAgent = null): void
    {
        self::create([
            'certificate_id' => $certificateId,
            'event_type' => $eventType,
            'site' => $site ? substr($site, 0, 500) : null,
            'ip_address' => $ip ? substr($ip, 0, 45) : null,
            'user_agent' => $userAgent,
        ]);
    }
}
