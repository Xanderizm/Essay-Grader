<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tarik jawaban baru dari Google Forms tiap menit
Schedule::command('exams:sync')->everyMinute()->withoutOverlapping();

// Cadangan database harian dan pembersihan antrean gagal
Schedule::command('db:backup')->dailyAt('02:00');
Schedule::command('queue:prune-failed --hours=168')->daily();

// Penghapusan data lama, hanya aktif jika RETENTION_DAYS diisi di .env
if (config('retention.days')) {
    Schedule::command('exams:prune --force')->dailyAt('03:00');
}