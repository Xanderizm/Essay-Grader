<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $guarded = [];
    public const TYPE_LABELS = [
        'short_answer' => 'Jawaban singkat', 'paragraph' => 'Paragraf', 'multiple_choice' => 'Pilihan ganda',
        'checkbox' => 'Kotak centang', 'dropdown' => 'Drop-down', 'file_upload' => 'Upload file',
        'linear_scale' => 'Skala linear', 'rating' => 'Rating', 'multiple_choice_grid' => 'Kisi pilihan ganda',
        'checkbox_grid' => 'Kisi kotak centang', 'date' => 'Tanggal', 'time' => 'Waktu',
    ];
    public const ROLE_LABELS = ['identity' => 'Identitas Responden', 'stimulus' => 'Stimulus Media', 'main' => 'Konten Utama'];
    public const IDENTITY_TYPES = ['short_answer', 'paragraph', 'multiple_choice', 'dropdown', 'checkbox', 'date', 'file_upload'];
    public const COLUMN_TYPES   = ['short_answer', 'dropdown', 'multiple_choice'];   // kolom tabel, sort, deteksi duplikat
    public const TEXT_TYPES     = ['paragraph', 'short_answer'];                      // jenis yang diketik responden dan bisa dinilai

    public function scopeIdentityColumns($query)
    {
        return $query->where('field_type', 'identity')->whereIn('gform_item_type', self::COLUMN_TYPES);
    }

    public function scopeScoring($query)
    {
        return $query->whereIn('gform_item_type', self::TEXT_TYPES)->where('grading_mode', 'scoring');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}