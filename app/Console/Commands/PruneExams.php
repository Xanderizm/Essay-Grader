<?php

namespace App\Console\Commands;

use App\Models\Exam;
use Illuminate\Console\Command;

class PruneExams extends Command
{
    protected $signature = 'exams:prune {--days= : Batas hari (bawaan dari RETENTION_DAYS)} {--force : Benar-benar menghapus (tanpa ini hanya simulasi)}';

    protected $description = 'Hapus penilaian tidak aktif yang sudah melewati batas penyimpanan';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('retention.days'));

        if ($days < 1) {
            $this->info('Batas penyimpanan belum diatur (RETENTION_DAYS atau --days). Tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $exams = Exam::where('status', '!=', 'active')
            ->where('updated_at', '<', $cutoff)
            ->whereDoesntHave('submissions', fn ($q) => $q->where('updated_at', '>=', $cutoff))
            ->get();

        if ($exams->isEmpty()) {
            $this->info('Tidak ada penilaian yang melewati batas.');

            return self::SUCCESS;
        }

        foreach ($exams as $exam) {
            $this->line("#{$exam->id} {$exam->title} (user {$exam->user_id}, terakhir diubah {$exam->updated_at})");
        }

        if (! $this->option('force')) {
            $this->warn($exams->count() . ' penilaian akan dihapus. Ini hanya simulasi; tambahkan --force untuk menghapus.');

            return self::SUCCESS;
        }

        foreach ($exams as $exam) {
            $exam->delete(); // pertanyaan, pengiriman, dan jawaban ikut terhapus
        }

        $this->info($exams->count() . ' penilaian dihapus.');

        return self::SUCCESS;
    }
}