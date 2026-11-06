<?php

namespace App\Console\Commands;

use App\Jobs\SyncExamResponses;
use App\Models\Exam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncResponses extends Command
{
    protected $signature = 'exams:sync';

    protected $description = 'Tarik jawaban terbaru dari Google Forms untuk semua penilaian aktif';

    public function handle(): int
    {
        Cache::put('scheduler:last_run', now()->timestamp, 86400);        
        $exams = Exam::where('status', 'active')
            ->where('analysis_status', 'success')
            ->whereHas('user', fn ($q) => $q->whereNull('deletion_requested_at'))
            ->get();

        foreach ($exams as $exam) {
            SyncExamResponses::dispatch($exam);
        }

        $this->info($exams->count() . ' penilaian dijadwalkan.');

        return self::SUCCESS;
    }
}