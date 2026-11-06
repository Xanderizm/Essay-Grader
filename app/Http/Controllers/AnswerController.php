<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEssayGrading;
use App\Models\StudentAnswer;
use App\Services\EssayGraderService;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    public function override(Request $request, StudentAnswer $answer, EssayGraderService $grader)
    {
        $this->authorizeAnswer($answer);

        // Jawaban yang sedang dinilai AI tidak boleh dikoreksi agar tidak saling menimpa
        if (! in_array($answer->processing_status, ['success', 'failed'], true)) {
            return back()->withErrors(['status' => 'Jawaban ini sedang diproses. Coba lagi setelah selesai dinilai.']);
        }

        $data = $request->validate([
            'score_content'   => 'required|integer|min:0|max:100',
            'score_logic'     => 'required|integer|min:0|max:100',
            'score_structure' => 'required|integer|min:0|max:100',
            'override_note'   => 'nullable|string|max:1000',
        ]);

        $summary = $grader->summarize(
            (int) $data['score_content'],
            (int) $data['score_logic'],
            (int) $data['score_structure']
        );

        // Simpan nilai AI yang asli hanya pada koreksi pertama
        $original = $answer->ai_original;

        if (! $answer->is_overridden && $answer->processing_status === 'success' && $answer->final_score !== null) {
            $original = [
                'score_content'   => $answer->score_content,
                'score_logic'     => $answer->score_logic,
                'score_structure' => $answer->score_structure,
                'final_score'     => $answer->final_score,
                'category'        => $answer->ai_category,
            ];
        }

        $note = trim((string) ($data['override_note'] ?? ''));

        $answer->update([
            'score_content'     => (int) $data['score_content'],
            'score_logic'       => (int) $data['score_logic'],
            'score_structure'   => (int) $data['score_structure'],
            'final_score'       => $summary['final_score'],
            'ai_category'       => $summary['category'],
            'is_overridden'     => true,
            'override_note'     => $note !== '' ? $note : null,
            'overridden_at'     => now(),
            'overridden_by'     => auth()->id(),
            'ai_original'       => $original,
            'processing_status' => 'success',
            'last_error'        => null,
        ]);

        return back()->with('status', 'Koreksi disimpan. Nilai akhir: ' . number_format($summary['final_score'], 1) . ' (' . $summary['category'] . ').');
    }

    public function revert(StudentAnswer $answer)
    {
        $this->authorizeAnswer($answer);

        if (! $answer->is_overridden) {
            return back()->with('status', 'Jawaban ini belum dikoreksi.');
        }

        $reset = [
            'is_overridden' => false,
            'override_note' => null,
            'overridden_at' => null,
            'overridden_by' => null,
            'ai_original'   => null,
        ];

        // Ada nilai AI asli: kembalikan langsung
        if ($answer->ai_original) {
            $o = $answer->ai_original;

            $answer->update($reset + [
                'score_content'   => $o['score_content'],
                'score_logic'     => $o['score_logic'],
                'score_structure' => $o['score_structure'],
                'final_score'     => $o['final_score'],
                'ai_category'     => $o['category'],
            ]);

            return back()->with('status', 'Nilai dikembalikan ke hasil AI.');
        }

        // Tidak ada nilai AI asli (dulu gagal, lalu dinilai manual): minta AI menilai sekarang
        $answer->update($reset + [
            'score_content'     => null,
            'score_logic'       => null,
            'score_structure'   => null,
            'final_score'       => null,
            'ai_category'       => null,
            'ai_analysis'       => null,
            'processing_status' => 'pending',
            'attempts'          => 0,
            'last_error'        => null,
            'processed_at'      => null,
        ]);

        ProcessEssayGrading::dispatch();

        return back()->with('status', 'Koreksi dibatalkan. Jawaban dijadwalkan untuk dinilai AI.');
    }

    private function authorizeAnswer(StudentAnswer $answer): void
    {
        abort_unless($answer->submission->exam->user_id === auth()->id(), 403);
    }
}