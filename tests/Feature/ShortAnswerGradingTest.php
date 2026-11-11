<?php

namespace Tests\Feature;

use App\Jobs\ProcessEssayGrading;
use App\Models\AiApiKey;
use App\Models\Exam;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\Submission;
use App\Models\User;
use App\Services\EssayGraderService;
use App\Services\FormAnalyzerService;
use App\Services\ShortAnswerGrader;
use App\Support\IdentityFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShortAnswerGradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_filter_is_strict_and_general(): void
    {
        $identity = [
            'Nama', 'Nama Lengkap', 'Nomor Induk Mahasiswa (NIM)', 'Alamat e-mail aktif', 'Tanggal lahir',
            'NISN', 'Asal sekolah', 'Nama karyawan', 'Jabatan', 'Perusahaan / Instansi', 'Pendidikan terakhir',
            'Nomor KTP', 'Usia', 'Kota domisili', 'Jenis kelamin', 'Full name', 'Date of birth',
        ];
        foreach ($identity as $t) {
            $this->assertTrue(IdentityFilter::isIdentity($t), $t);
        }

        $content = [
            'Sebutkan nama ibu kota Indonesia', 'Nama protokol yang mengamankan HTTP',
            'Perintah artisan untuk migrasi database', 'Apa nama perusahaan pembuat Windows?',
            'Sebutkan jabatan tertinggi di sebuah perusahaan',
        ];
        foreach ($content as $t) {
            $this->assertFalse(IdentityFilter::isIdentity($t), $t);
        }
    }

    public function test_keyword_matching_without_ai(): void
    {
        $g   = app(ShortAnswerGrader::class);
        $key = $g->acceptedAnswers("HTTPS\nhypertext transfer protocol secure");

        $this->assertSame('exact', $g->match('https', $key));
        $this->assertSame('exact', $g->match('  HTTPS. ', $key));
        $this->assertSame('contains', $g->match('Protokol https', $key));
        $this->assertSame('typo', $g->match('httpss', $key));
        $this->assertNull($g->match('bukan https', $key));
        $this->assertNull($g->match('ftp', $key));

        $artisan = $g->acceptedAnswers('php artisan migrate');
        $this->assertSame('contains', $g->match('php artisan migrate --force', $artisan));
    }

    public function test_classify_separates_identity_scoring_and_non_scoring_short_answers(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' =>
            '[{"question_id":"s2","identitas":false,"dinilai":true,"bentuk_kalimat":"perintah","alasan":"jawaban pasti"},'
            . '{"question_id":"s3","identitas":false,"dinilai":false,"bentuk_kalimat":"pertanyaan","alasan":"opini"},'
            . '{"question_id":"s4","identitas":true,"dinilai":false,"bentuk_kalimat":"pertanyaan","alasan":"data diri"}]']]]])]);
        AiApiKey::create(['provider_name' => 'groq', 'label' => 'k', 'api_key' => 'gsk_test_key_00000000000', 'is_active' => true]);

        $fields = [
            ['question_id' => 's1', 'title' => 'Nama lengkap', 'description' => '', 'type' => 'short_answer'],
            ['question_id' => 's2', 'title' => 'Perintah artisan untuk migrasi database', 'description' => '', 'type' => 'short_answer'],
            ['question_id' => 's3', 'title' => 'Apa pendapat Anda tentang kuliah ini?', 'description' => '', 'type' => 'short_answer'],
            ['question_id' => 's4', 'title' => 'Nama ibu kandung', 'description' => '', 'type' => 'short_answer'],
        ];
        $out = collect(app(FormAnalyzerService::class)->classify('Uji', $fields))->keyBy('question_id');

        $this->assertSame(['identity', null], [$out['s1']['role'], $out['s1']['grading']]);
        $this->assertSame(['main', 'scoring'], [$out['s2']['role'], $out['s2']['grading']]);
        $this->assertSame(['main', 'non_scoring'], [$out['s3']['role'], $out['s3']['grading']]);
        $this->assertSame(['identity', null], [$out['s4']['role'], $out['s4']['grading']]);

        Http::assertSent(fn ($r) => str_contains(json_encode($r->data()), 'migrasi') && ! str_contains(json_encode($r->data()), 'Nama lengkap'));
    }

    public function test_job_matches_by_code_and_sends_only_unresolved_answers_to_ai_once(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => '[{"id":1,"ok":false}]']]]])]);
        AiApiKey::create(['provider_name' => 'groq', 'label' => 'k', 'api_key' => 'gsk_test_key_00000000000', 'is_active' => true]);

        $user = User::factory()->create();
        $exam = Exam::create([
            'user_id' => $user->id, 'gform_id' => 'FORM_UJI', 'title' => 'Uji', 'analysis_status' => 'success',
            'status' => 'active', 'webhook_token' => Str::random(40),
        ]);
        $q = Question::create([
            'exam_id' => $exam->id, 'gform_item_id' => 'q1', 'question_text' => 'Protokol aman web?',
            'field_type' => 'main', 'gform_item_type' => 'short_answer', 'grading_mode' => 'scoring',
            'answer_key' => 'HTTPS', 'order' => 1,
        ]);

        $answers = [];
        foreach (['HTTPS', 'protokol https', 'ftp', ''] as $i => $text) {
            $s = Submission::create(['exam_id' => $exam->id, 'gform_response_id' => 'r' . $i]);
            $answers[$text] = StudentAnswer::create([
                'submission_id' => $s->id, 'question_id' => $q->id, 'raw_answer' => $text, 'processing_status' => 'pending',
            ]);
        }

        (new ProcessEssayGrading())->handle(app(EssayGraderService::class), app(ShortAnswerGrader::class));

        $this->assertEquals(100, $answers['HTTPS']->fresh()->final_score);
        $this->assertEquals(100, $answers['protokol https']->fresh()->final_score);
        $this->assertEquals(0, $answers['ftp']->fresh()->final_score);
        $this->assertEquals(0, $answers['']->fresh()->final_score);
        $this->assertSame('success', $answers['ftp']->fresh()->processing_status);

        // Hanya SATU panggilan AI, dan hanya berisi jawaban yang belum cocok
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => str_contains(json_encode($r->data()), 'ftp') && ! str_contains(json_encode($r->data()), 'protokol https'));
    }
}