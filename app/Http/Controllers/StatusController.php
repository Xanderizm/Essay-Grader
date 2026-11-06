<?php

namespace App\Http\Controllers;

use App\Models\AiApiKey;
use App\Models\Exam;
use App\Models\StudentAnswer;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function index()
    {
        $now = time();

        // Antrean: pekerjaan yang sudah siap jalan tetapi menunggu terlalu lama berarti pekerja mati
        $ready      = DB::table('jobs')->where('available_at', '<=', $now)->whereNull('reserved_at');
        $readyCount = (clone $ready)->count();
        $oldest     = (clone $ready)->min('available_at');
        $lag        = $oldest ? $now - (int) $oldest : 0;

        $schedulerLast = Cache::get('scheduler:last_run');
        $schedulerAge  = $schedulerLast ? $now - (int) $schedulerLast : null;

        $keys     = AiApiKey::all();
        $keyStats = [
            'total'   => $keys->count(),
            'active'  => $keys->where('is_active', true)->count(),
            'cooling' => $keys->filter(fn ($k) => $k->is_active && $k->cooldown_until && $k->cooldown_until->isFuture())->count(),
        ];

        $dir        = storage_path('app/backups');
        $files      = File::isDirectory($dir)
            ? collect(File::files($dir))->filter(fn ($f) => $f->getExtension() === 'sql')
            : collect();
        $lastBackup = $files->isEmpty() ? null : $files->max(fn ($f) => $f->getMTime());
        $backupAge  = $lastBackup ? $now - $lastBackup : null;

        return view('status', [
            'readyCount'    => $readyCount,
            'lag'           => $lag,
            'schedulerAge'  => $schedulerAge,
            'keyStats'      => $keyStats,
            'backupAge'     => $backupAge,
            'failedJobs'    => DB::table('failed_jobs')->count(),
            'users'         => User::count(),
            'examsByStatus' => Exam::selectRaw('status, COUNT(*) AS c')->groupBy('status')->pluck('c', 'status'),
            'answers'       => StudentAnswer::selectRaw('processing_status, COUNT(*) AS c')->groupBy('processing_status')->pluck('c', 'processing_status'),
            'failedAnswers' => StudentAnswer::where('processing_status', 'failed')
                ->latest('updated_at')->limit(5)->get(['id', 'last_error', 'updated_at']),
            'googleUsers' => User::whereNotNull('google_refresh_token')->count(),
            'mailDriver'  => config('mail.default'),
            'mailFrom'    => config('mail.from.address'),
        ]);
    }

    public function testMail(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Mail::raw('Email uji dari Penilai Esai. Jika Anda menerima ini, pengiriman email berfungsi.',
                fn ($m) => $m->to($request->user()->email)->subject('Email uji Penilai Esai'));
        } catch (\Throwable $e) {
            return back()->with('status', 'Gagal mengirim: ' . \Illuminate\Support\Str::limit($e->getMessage(), 160));
        }

        return back()->with('status', 'Email uji dikirim ke ' . $request->user()->email . '.');
    }
}