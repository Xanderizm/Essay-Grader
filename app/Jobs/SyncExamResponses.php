<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Services\ResponseSyncService;
use App\Services\FormGoneException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncExamResponses implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $uniqueFor = 120;               // tidak menumpuk: satu penarikan per penilaian pada satu waktu
    public $deleteWhenMissingModels = true;

    public function __construct(public Exam $exam)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->exam->id;
    }

    public function handle(ResponseSyncService $sync): void
    {
        $exam = $this->exam->fresh();

        if (! $exam || $exam->status !== 'active' || $exam->analysis_status !== 'success') {
            return;
        }

        try {
            $sync->sync($exam);
        } catch (FormGoneException $e) {
            $exam->update([
                'status'           => 'closed',
                'gform_deleted_at' => now(),
                'sync_error'       => 'Google Form sudah dihapus atau tidak dapat diakses. Penilaian dinonaktifkan otomatis.',
            ]);
        } catch (\Throwable $e) {
            $exam->update(['sync_error' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }
}