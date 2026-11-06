<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEssayGrading;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function update(Request $request, Question $question)
    {
        $this->authorizeQuestion($question);

        $data = $request->validate([
            'answer_key'   => 'nullable|string|max:5000',
            'grading_mode' => 'required|in:scoring,non_scoring',
        ]);

        $changes = ['grading_mode' => $data['grading_mode']];
        if ($request->has('answer_key')) {
            $changes['answer_key'] = trim((string) $data['answer_key']) ?: null;   // empty rubric is allowed
        }
        $becameScoring = $data['grading_mode'] === 'scoring' && $question->grading_mode !== 'scoring';

        $question->update($changes);

        if ($becameScoring) { ProcessEssayGrading::dispatch(); }

        return back()->with('status', 'Pengaturan disimpan. Jawaban yang sudah dinilai tidak berubah otomatis; klik "Nilai ulang" untuk memakai rubrik baru.');
    }

    public function regrade(Question $question)
    {
        $this->authorizeQuestion($question);

        if ($question->grading_mode !== 'scoring') {
            return back()->withErrors(['status' => 'Pertanyaan ini diatur "Tidak dinilai", jadi tidak diproses AI.']);
        }

        $count = $question->answers()->where('is_overridden', false)->update([
            'processing_status' => 'pending',
            'attempts'          => 0,
            'last_error'        => null,
            'score_content'     => null,
            'score_logic'       => null,
            'score_structure'   => null,
            'final_score'       => null,
            'ai_category'       => null,
            'ai_analysis'       => null,
            'processed_at'      => null,
        ]);

        if ($count > 0) {
            ProcessEssayGrading::dispatch();
        }

        $skipped = $question->answers()->where('is_overridden', true)->count();

        return back()->with('status', "$count jawaban dijadwalkan untuk dinilai ulang."
            . ($skipped > 0 ? " $skipped jawaban yang sudah dikoreksi pembuat dilewati." : ''));
    }

    private function authorizeQuestion(Question $question): void
    {
        abort_unless(
            $question->gform_item_type === 'paragraph' && $question->exam->user_id === auth()->id(),
            403
        );
    }
}