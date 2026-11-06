<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Services\FormAnalyzerService;
use App\Services\GoogleClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeExamForm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    // Kalau penilaian dihapus sebelum job berjalan, job dibuang tanpa error
    public $deleteWhenMissingModels = true;

    public function __construct(public Exam $exam)
    {
    }

    public function handle(GoogleClient $google, FormAnalyzerService $analyzer): void
    {
        $exam = $this->exam->fresh();

        if (! $exam) {
            return;
        }

        $exam->update(['analysis_status' => 'analyzing', 'analysis_error' => null]);

        try {
            $form = $google->getForm($exam->user, $exam->gform_id);
        } catch (\Throwable $e) {
            $exam->update([
                'analysis_status' => 'failed',
                'analysis_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);
            return;
        }

        // Hasilnya (success/failed) dicatat sendiri oleh analyzer
        $analyzer->analyzeStructure($exam, $form);
    }

    public function failed(\Throwable $e): void
    {
        $this->exam->update([
            'analysis_status' => 'failed',
            'analysis_error'  => mb_substr('Kesalahan tak terduga: ' . $e->getMessage(), 0, 500),
        ]);
    }
}