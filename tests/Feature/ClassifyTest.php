<?php

namespace Tests\Feature;

use App\Models\AiApiKey;
use App\Services\FormAnalyzerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClassifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_types_come_from_form_and_ai_only_sees_paragraphs(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' =>
            '[{"question_id":"p1","dinilai":true,"bentuk_kalimat":"perintah","alasan":"uji konsep"},{"question_id":"p2","dinilai":false,"bentuk_kalimat":"pertanyaan","alasan":"opini"}]']]]])]);
        AiApiKey::create(['provider_name' => 'groq', 'label' => 'k', 'api_key' => 'gsk_test_key_00000000000', 'is_active' => true]);

        $fields = [
            ['question_id' => 's1', 'title' => 'Nama', 'description' => '', 'type' => 'short_answer'],
            ['question_id' => 'p1', 'title' => 'Jelaskan konsep X', 'description' => '', 'type' => 'paragraph'],
            ['question_id' => 'p2', 'title' => 'Saran Anda', 'description' => '', 'type' => 'paragraph'],
            ['question_id' => 'm1', 'title' => 'Pilih satu', 'description' => '', 'type' => 'multiple_choice'],
        ];
        $out = collect(app(FormAnalyzerService::class)->classify('Uji', $fields))->keyBy('question_id');

        $this->assertSame(['identity', null, null], [$out['s1']['role'], $out['s1']['grading'], $out['s1']['kind']]);
        $this->assertSame(['main', 'scoring', 'instruction'], [$out['p1']['role'], $out['p1']['grading'], $out['p1']['kind']]);
        $this->assertSame(['main', 'non_scoring', 'question'], [$out['p2']['role'], $out['p2']['grading'], $out['p2']['kind']]);
        $this->assertSame(['main', null], [$out['m1']['role'], $out['m1']['grading']]);

        Http::assertSent(fn ($r) => str_contains(json_encode($r->data()), 'Jelaskan konsep X') && ! str_contains(json_encode($r->data()), 'Pilih satu'));
    }

    public function test_no_ai_call_when_form_has_no_paragraph(): void
    {
        Http::fake();

        app(FormAnalyzerService::class)->classify('Uji', [
            ['question_id' => 's1', 'title' => 'Nama', 'description' => '', 'type' => 'short_answer'],
        ]);

        Http::assertNothingSent();
    }
}