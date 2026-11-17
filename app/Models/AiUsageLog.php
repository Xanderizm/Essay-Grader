<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'success'         => 'boolean',
        'used_shared_key' => 'boolean',
    ];

    /** Pencatatan tidak boleh pernah menggagalkan proses penilaian. */
    public static function record(array $data): void
    {
        try {
            $data['total_tokens'] ??= ($data['prompt_tokens'] ?? 0) + ($data['completion_tokens'] ?? 0);
            static::create($data);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}