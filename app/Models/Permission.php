<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function certificates(): BelongsToMany
    {
        return $this->belongsToMany(Certificate::class);
    }
}
