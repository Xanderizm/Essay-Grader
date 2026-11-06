<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\StudentAnswer;
use Illuminate\Support\Carbon;

class LiveState
{
    /** Ringkasan murah untuk polling. "version" berubah hanya jika data penting berubah. */
    public static function snapshot(Exam $exam): array
    {
        $ids    = $exam->questions()->scoring()->pluck('id');
        $counts = StudentAnswer::whereIn('question_id', $ids)
            ->selectRaw('processing_status, COUNT(*) AS c')->groupBy('processing_status')->pluck('c', 'processing_status');

        $data = [
            'analysis'    => $exam->analysis_status,
            'submissions' => $exam->submissions()->count(),
            'success'     => (int) ($counts['success'] ?? 0),
            'pending'     => (int) (($counts['pending'] ?? 0) + ($counts['processing'] ?? 0)),
            'failed'      => (int) ($counts['failed'] ?? 0),
            'stamp'       => (string) StudentAnswer::whereIn('question_id', $ids)->max('updated_at'),
            'error'       => Friendly::error($exam->sync_error),
        ];

        $data['version'] = md5(json_encode($data));
        $data['synced']  = $exam->last_synced_at
            ? Carbon::parse($exam->last_synced_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s')
            : 'belum pernah';

        return $data;   // "synced" sengaja di luar version agar tabel tidak dimuat ulang tiap sinkronisasi
    }
}
