<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'final_score'   => 'float',
        'processed_at'  => 'datetime',
        'overridden_at' => 'datetime',
        'is_overridden' => 'boolean',
        'ai_original'   => 'array',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}