<?php

namespace App\Services;

use App\Jobs\ProcessEssayGrading;
use App\Models\Exam;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\Submission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResponseSyncService
{
    public function __construct(private GoogleClient $google)
    {
    }

    /** @return array ['fetched' => jumlah diambil, 'new' => pengiriman baru, 'queued' => yang perlu dinilai] */
    public function sync(Exam $exam): array
    {
        $startedAt = now();

        // Tumpang tindih 10 menit; aman karena penyimpanan bersifat idempotent
        $since = $exam->last_synced_at
            ? Carbon::parse($exam->last_synced_at)->subMinutes(10)
            : null;

        $responses = $this->google->listResponses($exam->user, $exam->gform_id, $since);

        $questions      = $exam->questions()->whereNotNull('gform_item_id')->get()->keyBy('gform_item_id');
        $essayQuestions = $questions->whereIn('gform_item_type', Question::TEXT_TYPES)->whereNotNull('grading_mode');

        $stats = ['fetched' => count($responses), 'new' => 0, 'queued' => 0];

        foreach ($responses as $response) {
            [$isNew, $queued] = $this->store($exam, $questions, $essayQuestions, $response);

            $stats['new']    += $isNew ? 1 : 0;
            $stats['queued'] += $queued ? 1 : 0;
        }

        $exam->update(['last_synced_at' => $startedAt, 'sync_error' => null]);

        if ($stats['queued'] > 0) {
            ProcessEssayGrading::dispatch()->delay(now()->addSeconds(5));
        }

        return $stats;
    }

    private function store(Exam $exam, Collection $questions, Collection $essayQuestions, array $response): array
    {
        $responseId = (string) ($response['responseId'] ?? '');

        if ($responseId === '') {
            return [false, false];
        }

        $identity = [];
        $keyParts = [];   // baru: hanya kolom pendek untuk deteksi duplikat
        $essays   = [];

        // Pisahkan jawaban identitas dan esai berdasarkan ID kolom dari Google
        foreach (($response['answers'] ?? []) as $questionId => $answer) {
            $q = $questions->get((string) $questionId);
    
            if (! $q) {
                continue; // kolom yang tidak dikenal diabaikan
            }

            $text = $this->textOf((array) $answer);

            if ($q->field_type === 'identity') {
                $identity[$q->id] = $text;
                if (in_array($q->gform_item_type, Question::COLUMN_TYPES, true)) { $keyParts[$q->id] = $text; }
            }
            if (in_array($q->gform_item_type, Question::TEXT_TYPES, true) && $q->grading_mode !== null) {
                $essays[$q->id] = $text;
            }
        }

        // Cadangan: alamat email responden jika Form mengumpulkannya
        $identity = array_filter($identity, fn ($v) => $v !== '');
        $keyParts = array_filter($keyParts, fn ($v) => $v !== '');

        $time = $response['lastSubmittedTime'] ?? $response['createTime'] ?? null;

        return DB::transaction(function () use ($exam, $responseId, $identity, $keyParts, $essays, $essayQuestions, $response, $time) {
            $submission = Submission::firstOrNew([
                'exam_id'           => $exam->id,
                'gform_response_id' => $responseId,
            ]);

            $isNew = ! $submission->exists;

            $submission->fill([
                'identity'     => $identity ?: null,
                'identity_key' => $keyParts ? mb_substr(mb_strtolower(implode('|', $keyParts)), 0, 191) : null,
                'submitted_at' => $time ? Carbon::parse($time)->setTimezone(config('app.timezone')) : null,
                'raw_payload'  => $response,
            ])->save();

            $queued = false;

            foreach ($essayQuestions as $q) {
                // Esai yang dikosongkan responden tidak dikirim Google; dicatat sebagai teks kosong
                $text   = $essays[$q->id] ?? '';
                $answer = StudentAnswer::firstOrNew([
                    'submission_id' => $submission->id,
                    'question_id'   => $q->id,
                ]);

                if (! $answer->exists) {
                    $answer->fill(['raw_answer' => $text, 'processing_status' => 'pending'])->save();
                    $queued = true;
                } elseif ((string) $answer->raw_answer !== $text) {
                    // Jawaban berubah (responden mengedit): nilai lama dibuang dan dinilai ulang
                    $answer->fill([
                        'raw_answer'        => $text,
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
                        'is_overridden'     => false,
                        'override_note'     => null,
                        'overridden_at'     => null,
                        'overridden_by'     => null,
                        'ai_original'       => null,                        
                    ])->save();
                    $queued = true;
                }
            }

            return [$isNew, $queued];
        });
    }

    // Gabungkan semua nilai jawaban satu kolom menjadi satu teks (kotak centang bisa lebih dari satu)
    private function textOf(array $answer): string
    {
        $values = array_map(
            fn ($a) => (string) ($a['value'] ?? ''),
            (array) ($answer['textAnswers']['answers'] ?? [])
        );

        return trim(implode(', ', array_filter($values, fn ($v) => $v !== '')));
    }

    private function clip(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
