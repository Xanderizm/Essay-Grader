<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep= : Jumlah file terbaru yang disimpan (bawaan dari config/backup.php)}';

    protected $description = 'Cadangkan database MySQL ke storage/app/backups';

    public function handle(): int
    {
        $config = config('database.connections.' . config('database.default'));

        if (($config['driver'] ?? null) !== 'mysql') {
            $this->error('Perintah ini hanya mendukung MySQL.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $file = $dir . DIRECTORY_SEPARATOR . $config['database'] . '_' . now()->format('Ymd_His') . '.sql';

        // Password dikirim lewat variabel lingkungan agar tidak tampak di daftar proses
        $process = new Process([
            config('backup.mysqldump'),
            '--host=' . $config['host'],
            '--port=' . $config['port'],
            '--user=' . $config['username'],
            '--single-transaction',
            '--routines',
            '--default-character-set=utf8mb4',
            '--result-file=' . $file,
            $config['database'],
        ], null, ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);

        $process->setTimeout(300);

        try {
            $process->run();
        } catch (\Throwable $e) {
            $this->error('mysqldump tidak bisa dijalankan: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (! $process->isSuccessful() || ! File::exists($file) || File::size($file) < 1000) {
            File::delete($file);
            $this->error('Cadangan gagal. ' . trim($process->getErrorOutput()));

            return self::FAILURE;
        }

        // Simpan hanya N file terbaru
        $keep  = max(1, (int) ($this->option('keep') ?: config('backup.keep')));
        $files = collect(File::files($dir))
            ->filter(fn ($f) => $f->getExtension() === 'sql')
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        foreach ($files->slice($keep) as $old) {
            File::delete($old->getPathname());
        }

        $this->info('Cadangan dibuat: ' . $file . ' (' . round(File::size($file) / 1024) . ' KB)');

        return self::SUCCESS;
    }
}