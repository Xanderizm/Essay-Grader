<?php

namespace Tests\Feature;

use App\Services\EssayGraderService;
use App\Services\FormAnalyzerService;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

// Catatan: angka batas kategori mengikuti bawaan config/grading.php (85/70/55).
class GradingLogicTest extends TestCase
{
    private function grader(): EssayGraderService
    {
        return app(EssayGraderService::class);
    }

    private function parse(string $content, array $ids): array
    {
        $m = new ReflectionMethod($this->grader(), 'parse');

        return $m->invoke($this->grader(), $content, $ids);
    }

    public function test_category_boundaries(): void
    {
        $g = $this->grader();

        $this->assertSame('Sangat Baik', $g->categoryFor(85.0));
        $this->assertSame('Baik', $g->categoryFor(84.99));
        $this->assertSame('Baik', $g->categoryFor(70.0));
        $this->assertSame('Cukup', $g->categoryFor(69.99));
        $this->assertSame('Cukup', $g->categoryFor(55.0));
        $this->assertSame('Kurang', $g->categoryFor(54.99));
        $this->assertSame('Kurang', $g->categoryFor(0.0));
    }

    public function test_summarize_averages_three_scores(): void
    {
        $r = $this->grader()->summarize(90, 80, 70);

        $this->assertEquals(80.0, $r['final_score']);
        $this->assertSame('Baik', $r['category']);
        $this->assertSame('Kurang', $this->grader()->summarize(0, 0, 0)['category']);
    }

    public function test_parse_handles_markdown_and_chatter(): void
    {
        $r = $this->parse(
            "Berikut hasilnya:\n```json\n[{\"answer_id\": 1, \"content\": 90, \"logic\": 80, \"structure\": 70, \"analysis\": \"Bagus\"}]\n```\nSemoga membantu.",
            [1]
        );

        $this->assertSame(90, $r[1]['content']);
        $this->assertSame('Bagus', $r[1]['analysis']);
    }

    public function test_parse_clamps_and_applies_penalty_rule(): void
    {
        $r = $this->parse('[{"answer_id": 1, "content": 20, "logic": 30, "structure": 60, "analysis": "x"},'
            . '{"answer_id": 2, "content": 150, "logic": -5, "structure": 70.6, "analysis": "y"}]', [1, 2]);

        // Konten di bawah 30: logika dan struktur dibatasi maksimal 40
        $this->assertSame(20, $r[1]['content']);
        $this->assertSame(30, $r[1]['logic']);
        $this->assertSame(40, $r[1]['structure']);

        // Di luar 0-100 dikoreksi, desimal dibulatkan
        $this->assertSame(100, $r[2]['content']);
        $this->assertSame(0, $r[2]['logic']);
        $this->assertSame(71, $r[2]['structure']);
    }

    public function test_parse_drops_incomplete_and_unknown_rows(): void
    {
        $r = $this->parse('[{"answer_id": 1, "content": 50, "logic": 50, "structure": 50},'
            . '{"answer_id": 2, "content": 50, "structure": 50},'
            . '{"answer_id": 99, "content": 50, "logic": 50, "structure": 50}]', [1, 2]);

        $this->assertSame([1], array_keys($r));
    }

    public function test_parse_rejects_non_json(): void
    {
        $this->expectException(RuntimeException::class);

        $this->parse('Maaf, saya tidak bisa menilai ini.', [1]);
    }

    public function test_form_id_extraction(): void
    {
        $a = app(FormAnalyzerService::class);

        $this->assertSame(
            '1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789',
            $a->extractFormId('https://docs.google.com/forms/d/1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789/edit')
        );
        $this->assertSame(
            '1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789',
            $a->extractFormId('1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789')
        );
    }

    public function test_student_form_link_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);

        app(FormAnalyzerService::class)->extractFormId('https://docs.google.com/forms/d/e/1FAIpQLSdXXXXXXXXXXXXXXXX/viewform');
    }

    public function test_flatten_skips_non_questions_and_detects_types(): void
    {
        $form = ['items' => [
            ['title' => 'Nama', 'questionItem' => ['question' => ['questionId' => 'a1', 'textQuestion' => []]]],
            ['title' => 'Uraian', 'questionItem' => ['question' => ['questionId' => 'a2', 'textQuestion' => ['paragraph' => true]]]],
            ['title' => 'Pilih', 'questionItem' => ['question' => ['questionId' => 'a3', 'choiceQuestion' => ['type' => 'RADIO']]]],
            ['title' => 'Setuju', 'questionItem' => ['question' => ['questionId' => 'a4', 'choiceQuestion' => ['type' => 'CHECKBOX']]]],
            ['title' => 'Bagian Soal', 'pageBreakItem' => []],
            ['title' => 'Penilaian', 'questionGroupItem' => ['questions' => [
                ['questionId' => 'g1', 'rowQuestion' => ['title' => 'Baris 1']],
            ]]],
        ]];

        $fields = app(FormAnalyzerService::class)->flatten($form);

        $this->assertSame(['a1', 'a2', 'a3', 'a4', 'g1'], array_column($fields, 'question_id'));
        $this->assertSame(
            ['short_answer', 'paragraph', 'multiple_choice', 'checkbox', 'multiple_choice_grid'],
            array_column($fields, 'type')
        );
        $this->assertSame('Penilaian - Baris 1', $fields[4]['title']);
    }
}