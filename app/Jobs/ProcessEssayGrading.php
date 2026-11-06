<?php

namespace App\Jobs;

use App\Models\StudentAnswer;
use App\Services\EssayGraderService;
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

    private const BATCH_SIZE           = 5;   // jumlah jawaban per permintaan ke AI
    private const MAX_REQUESTS_PER_MIN = 3;   // batas permintaan ke AI per menit
    private const MAX_ATTEMPTS         = 6;   // percobaan maksimal per jawaban

    public int $tries = 1;
    public int $uniqueFor = 120;

    public function handle(EssayGraderService $grader): void
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

        RateLimiter::hit('ai-grading', 60);

        $question = $toGrade->first()->question;

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

    /** Ambil sampai 5 jawaban pending untuk SATU pertanyaan yang sama, tandai "processing". */
    private function claimBatch()
    {
        return DB::transaction(function () {
            // Pulihkan jawaban yang tersangkut "processing" terlalu lama (mis. server mati)
            StudentAnswer::where('processing_status', 'processing')
                ->where('updated_at', '<', now()->subMinutes(10))
                ->update(['processing_status' => 'pending']);

            $first = StudentAnswer::where('processing_status', 'pending')
                ->whereHas('question', fn ($q) => $q->scoring())
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $first) {
                return collect();
            }

            $batch = StudentAnswer::with('question')
                ->where('processing_status', 'pending')
                ->where('question_id', $first->question_id)
                ->orderBy('id')
                ->limit(self::BATCH_SIZE)
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
        if (StudentAnswer::where('processing_status', 'pending')->whereHas('question', fn ($q) => $q->scoring())->exists()) {
            self::dispatch()->delay(now()->addSeconds($delaySeconds));
        }
    }
}