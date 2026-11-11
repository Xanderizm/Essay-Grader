<?php

namespace App\Jobs;

use App\Models\Question;
use App\Models\StudentAnswer;
use App\Services\EssayGraderService;
use App\Services\ShortAnswerGrader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class ProcessEssayGrading implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const BATCH_SIZE           = 5;   // jumlah jawaban esai per permintaan ke AI
    private const MAX_REQUESTS_PER_MIN = 3;   // batas permintaan ke AI per menit
    private const MAX_ATTEMPTS         = 6;   // percobaan maksimal per jawaban

    public int $tries = 1;
    public int $uniqueFor = 120;

    public function handle(EssayGraderService $grader, ShortAnswerGrader $short): void
    {
        // Patuh pada batas permintaan per menit
        if (RateLimiter::tooManyAttempts('ai-grading', self::MAX_REQUESTS_PER_MIN)) {
            $this->scheduleNext(RateLimiter::availableIn('ai-grading') + 1);
            return;
        }

        $claimed = $this->claimBatch();

        if ($claimed->isEmpty()) {
            return;
        }

        // Jawaban kosong langsung diberi 0 tanpa memanggil AI
        [$blank, $toGrade] = $claimed->partition(fn ($a) => trim((string) $a->raw_answer) === '');

        foreach ($blank as $answer) {
            $this->saveResult($answer, $grader, [
                'content' => 0, 'logic' => 0, 'structure' => 0, 'analysis' => 'Jawaban kosong.',
            ]);
        }

        if ($toGrade->isEmpty()) {
            $this->scheduleNext(1);
            return;
        }

        $question = $toGrade->first()->question;

        // Jawaban singkat: pencocokan kunci lewat kode, AI hanya untuk sisa yang meragukan
        if ($question->gform_item_type === 'short_answer') {
            $this->gradeShortAnswers($question, $toGrade, $grader, $short);
            return;
        }

        // Paragraf: analisis naratif penuh 3 parameter oleh AI
        RateLimiter::hit('ai-grading', 60);

        $payload = $toGrade->map(fn ($a) => [
            'answer_id' => $a->id,
            'answer'    => $a->raw_answer,
        ])->values()->all();

        try {
            $results = $grader->grade($question, $payload);
        } catch (\Throwable $e) {
            $this->handleFailure($toGrade, $e);
            return;
        }

        foreach ($toGrade as $answer) {
            if (isset($results[$answer->id])) {
                $this->saveResult($answer, $grader, $results[$answer->id]);
            } else {
                $this->markRetry($answer, 'AI tidak mengembalikan nilai untuk jawaban ini');
            }
        }

        $this->scheduleNext(1);
    }

    private function gradeShortAnswers(Question $question, $answers, EssayGraderService $grader, ShortAnswerGrader $short): void
    {
        $accepted = $short->acceptedAnswers((string) $question->answer_key);

        if (! $accepted) {
            StudentAnswer::whereIn('id', $answers->pluck('id'))->update([
                'processing_status' => 'failed',
                'last_error'        => 'Kunci jawaban belum diisi.',
                'updated_at'        => now(),
            ]);
            $this->scheduleNext(1);
            return;
        }

        $unresolved = [];

        foreach ($answers as $answer) {
            if (ShortAnswerGrader::normalize((string) $answer->raw_answer) === '') {
                $this->saveShort($answer, $grader, false, 'Jawaban tidak berisi teks yang bisa dinilai.');
                continue;
            }

            $method = $short->match((string) $answer->raw_answer, $accepted);

            if ($method) {
                $this->saveShort($answer, $grader, true, $short->label($method));
            } else {
                $unresolved[] = $answer;
            }
        }

        if ($unresolved && ! config('grading.short_answer.ai_fallback', true)) {
            foreach ($unresolved as $answer) {
                $this->saveShort($answer, $grader, false, 'Tidak cocok dengan kunci jawaban.');
            }
            $unresolved = [];
        }

        if ($unresolved) {
            RateLimiter::hit('ai-grading', 60);

            try {
                $verdict = $short->semanticCheck($question, $accepted, collect($unresolved));
            } catch (\Throwable $e) {
                $this->handleFailure(collect($unresolved), $e);
                return;
            }

            foreach ($unresolved as $answer) {
                $ok = $verdict[ShortAnswerGrader::normalize((string) $answer->raw_answer)] ?? null;

                if ($ok === null) {
                    $this->markRetry($answer, 'AI tidak mengembalikan keputusan untuk jawaban ini');
                } else {
                    $this->saveShort($answer, $grader, $ok, $ok ? 'Cocok secara makna menurut AI.' : 'Tidak cocok dengan kunci jawaban.');
                }
            }
        }

        $this->scheduleNext(1);
    }

    private function saveShort(StudentAnswer $answer, EssayGraderService $grader, bool $correct, string $note): void
    {
        $score = $correct ? 100 : 0;

        $this->saveResult($answer, $grader, [
            'content' => $score, 'logic' => $score, 'structure' => $score, 'analysis' => $note,
        ]);
    }

    private function saveResult(StudentAnswer $answer, EssayGraderService $grader, array $r): void
    {
        $summary = $grader->summarize($r['content'], $r['logic'], $r['structure']);

        // Query langsung supaya semua kolom pasti tertulis ke database
        StudentAnswer::whereKey($answer->id)->update([
            'score_content'     => $r['content'],
            'score_logic'       => $r['logic'],
            'score_structure'   => $r['structure'],
            'final_score'       => $summary['final_score'],
            'ai_category'       => $summary['category'],
            'ai_analysis'       => $r['analysis'],
            'processing_status' => 'success',
            'last_error'        => null,
            'processed_at'      => now(),
            'updated_at'        => now(),
        ]);
    }

    /** Ambil jawaban pending untuk SATU pertanyaan yang sama (esai 5, jawaban singkat lebih banyak), tandai "processing". */
    private function claimBatch()
    {
        return DB::transaction(function () {
            // Pulihkan jawaban yang tersangkut "processing" terlalu lama (mis. server mati)
            StudentAnswer::where('processing_status', 'processing')
                ->where('updated_at', '<', now()->subMinutes(10))
                ->update(['processing_status' => 'pending']);

            $first = StudentAnswer::with('question')
                ->where('processing_status', 'pending')
                ->whereHas('question', fn ($q) => $q->scoring()->whereHas('exam', fn ($e) => $e->where('status', 'active')))
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $first) {
                return collect();
            }

            $limit = $first->question?->gform_item_type === 'short_answer'
                ? (int) config('grading.short_answer.batch_size', 40)
                : self::BATCH_SIZE;

            $batch = StudentAnswer::with('question')
                ->where('processing_status', 'pending')
                ->where('question_id', $first->question_id)
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            StudentAnswer::whereIn('id', $batch->pluck('id'))
                ->update(['processing_status' => 'processing']);

            return $batch;
        });
    }

    private function handleFailure($answers, \Throwable $e): void
    {
        $message = mb_substr($e->getMessage(), 0, 500);

        // Semua key sedang limit: kembalikan ke pending tanpa dihitung gagal, coba lagi 1 menit lagi
        if ($e->getCode() === 429) {
            StudentAnswer::whereIn('id', $answers->pluck('id'))
                ->update(['processing_status' => 'pending', 'last_error' => $message]);
            $this->scheduleNext(60);
            return;
        }

        foreach ($answers as $answer) {
            $this->markRetry($answer, $message);
        }

        $this->scheduleNext(30);
    }

    private function markRetry(StudentAnswer $answer, string $message): void
    {
        $attempts = $answer->attempts + 1;

        StudentAnswer::whereKey($answer->id)->update([
            'attempts'          => $attempts,
            'last_error'        => $message,
            'processing_status' => $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'pending',
            'updated_at'        => now(),
        ]);
    }

    /** Jadwalkan batch berikutnya hanya kalau masih ada jawaban pending. */
    private function scheduleNext(int $delaySeconds): void
    {
        if (StudentAnswer::where('processing_status', 'pending')->whereHas('question', fn ($q) => $q->scoring()->whereHas('exam', fn ($e) => $e->where('status', 'active')))->exists()) {
            self::dispatch()->delay(now()->addSeconds($delaySeconds));
        }
    }
}