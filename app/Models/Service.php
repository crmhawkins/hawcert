<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'endpoint',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function certificates(): BelongsToMany
    {
        return $this->belongsToMany(Certificate::class);
    }
}
