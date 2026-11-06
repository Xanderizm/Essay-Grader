<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw_payload'  => 'array',
        'submitted_at' => 'datetime',
        'identity' => 'array',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}