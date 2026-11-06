<?php

namespace Tests\Feature;

use App\Jobs\ProcessEssayGrading;
use App\Models\Exam;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\Submission;
use App\Models\User;
use App\Services\ResponseSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResponseSyncTest extends TestCase
{
    use RefreshDatabase;

    private function setupExam(): Exam
    {
        $user = User::factory()->create();
        $user->forceFill([
            'google_email'            => 'dosen@example.com',
            'google_token'            => Crypt::encryptString('token-uji'),
            'google_refresh_token'    => Crypt::encryptString('refresh-uji'),
            'google_token_expires_at' => now()->addHour(),
        ])->save();

        $exam = Exam::create([
            'user_id'         => $user->id,
            'gform_id'        => 'FORM_UJI',
            'title'           => 'Ujian Sinkron',
            'analysis_status' => 'success',
            'status'          => 'active',
            'webhook_token'   => Str::random(40),
        ]);

        foreach ([
            ['q-nama', 'Nama', 'identity', 'name', 'short_answer', 1],
            ['q-nim', 'NIM', 'identity', 'nim', 'short_answer', 2],
            ['q-e1', 'Soal 1', 'main', null, 'paragraph', 3],
            ['q-e2', 'Soal 2', 'main', null, 'paragraph', 4],
        ] as [$itemId, $title, $type, $role, $itemType, $order]) {
            Question::create([
                'exam_id'         => $exam->id,
                'gform_item_id'   => $itemId,
                'question_text'   => $title,
                'field_type'      => $type,
                'identity_role'   => $role,
                'gform_item_type' => $itemType,
                'answer_key'      => $type === 'main' ? 'Kunci' : null,
                'grading_mode'    => $type === 'main' ? 'scoring' : null,
                'order'           => $order,
            ]);
        }

        return $exam;
    }

    // Bentuk balasan Google Forms API; kolom Soal 2 sengaja tidak dikirim (dikosongkan mahasiswa)
    private function googleResponse(string $essay1): array
    {
        $answer = fn (string $id, string $value) => [
            $id => ['questionId' => $id, 'textAnswers' => ['answers' => [['value' => $value]]]],
        ];

        return ['responses' => [[
            'responseId'        => 'resp-1',
            'lastSubmittedTime' => '2026-10-22T08:00:00Z',
            'answers'           => $answer('q-nama', 'Andi') + $answer('q-nim', '2201001') + $answer('q-e1', $essay1),
        ]]];
    }

    private function essay1Answer(): StudentAnswer
    {
        return StudentAnswer::whereHas('question', fn ($q) => $q->where('gform_item_id', 'q-e1'))->firstOrFail();
    }

    public function test_sync_is_idempotent_and_detects_edited_answers(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake([
            'forms.googleapis.com/*' => Http::sequence()
                ->push($this->googleResponse('Jawaban awal'))
                ->push($this->googleResponse('Jawaban awal'))
                ->push($this->googleResponse('Jawaban diedit')),
        ]);

        $exam = $this->setupExam();
        $sync = app(ResponseSyncService::class);

        // Penarikan pertama: pengiriman baru, kolom kosong tetap dicatat
        $stats = $sync->sync($exam);
        $this->assertSame(1, $stats['new']);
        $this->assertSame(1, $stats['queued']);
        $this->assertSame(1, Submission::count());
        $this->assertSame(2, StudentAnswer::count());
        $this->assertContains('2201001', Submission::first()->identity);
        $this->assertContains('Andi', Submission::first()->identity);
        $this->assertSame('Jawaban awal', $this->essay1Answer()->raw_answer);
        Queue::assertPushed(ProcessEssayGrading::class);

        // Anggap sudah dinilai dan dikoreksi dosen
        $this->essay1Answer()->update([
            'processing_status' => 'success',
            'final_score'       => 80,
            'is_overridden'     => true,
            'override_note'     => 'koreksi',
        ]);

        // Penarikan kedua: data sama, tanpa duplikat dan tanpa reset
        $stats = $sync->sync($exam);
        $this->assertSame(0, $stats['new']);
        $this->assertSame(0, $stats['queued']);
        $this->assertSame(1, Submission::count());
        $this->assertSame(2, StudentAnswer::count());
        $this->assertSame('success', $this->essay1Answer()->processing_status);
        $this->assertTrue($this->essay1Answer()->is_overridden);

        // Penarikan ketiga: jawaban diedit mahasiswa, nilai dan koreksi dibuang, dinilai ulang
        $stats = $sync->sync($exam);
        $this->assertSame(1, $stats['queued']);

        $edited = $this->essay1Answer();
        $this->assertSame('Jawaban diedit', $edited->raw_answer);
        $this->assertSame('pending', $edited->processing_status);
        $this->assertFalse($edited->is_overridden);
        $this->assertNull($edited->final_score);
    }
}