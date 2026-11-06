<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiApiKey extends Model
{
    protected $guarded = [];

    protected $hidden = ['api_key'];

    protected $casts = [
        'api_key'        => 'encrypted',
        'is_active'      => 'boolean',
        'last_used_at'   => 'datetime',
        'cooldown_until' => 'datetime',
        'usage_date'     => 'date',
    ];
}