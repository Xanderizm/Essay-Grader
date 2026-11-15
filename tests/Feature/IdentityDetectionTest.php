<?php

namespace Tests\Feature;

use App\Models\AiApiKey;
use App\Services\FormAnalyzerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdentityDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_is_detected_across_field_types(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' =>
            '[{"question_id":"d3","identitas":true,"dinilai":false,"bentuk_kalimat":"pertanyaan","alasan":"data diri"}]']]]])]);
        AiApiKey::create(['provider_name' => 'groq', 'label' => 'k', 'api_key' => 'gsk_test_key_00000000000', 'is_active' => true]);

        $fields = [
            ['question_id' => 'd1', 'title' => 'Kelas', 'description' => '', 'type' => 'dropdown'],
            ['question_id' => 'd2', 'title' => 'Program Studi', 'description' => '', 'type' => 'multiple_choice'],
            ['question_id' => 'd3', 'title' => 'Asal daerah', 'description' => '', 'type' => 'dropdown'],
            ['question_id' => 'd4', 'title' => 'Pilih satu', 'description' => '', 'type' => 'multiple_choice'],
            ['question_id' => 'd5', 'title' => 'Tanggal lahir', 'description' => '', 'type' => 'date'],
        ];
        $out = collect(app(FormAnalyzerService::class)->classify('Uji', $fields))->keyBy('question_id');

        foreach (['d1', 'd2', 'd3', 'd5'] as $id) {
            $this->assertSame('identity', $out[$id]['role'], $id);
        }
        $this->assertSame('main', $out['d4']['role']);

        // Hanya drop-down yang belum terdeteksi yang dikirim ke AI
        Http::assertSent(fn ($r) => str_contains(json_encode($r->data()), 'Asal daerah')
            && ! str_contains(json_encode($r->data()), 'Program Studi')
            && ! str_contains(json_encode($r->data()), 'Pilih satu'));
    }
}