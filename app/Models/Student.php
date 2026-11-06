<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $guarded = [];

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}