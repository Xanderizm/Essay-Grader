<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\Exam;
use App\Models\StudentAnswer;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Statistik pribadi: SEMUA query dibatasi ke ujian milik user yang login. */
class UserStatsController extends Controller
{
    private const DAYS = 30;

    public function index(Request $request)
    {
        if ($request->user()->is_admin) {
            return redirect()->route('admin.monitoring');
        }

        $user = $request->user();
        $uid  = $user->id;

        $since    = now()->subDays(self::DAYS - 1)->startOfDay();
        $prevFrom = $since->copy()->subDays(self::DAYS);
        $weekAgo  = now()->subDays(7);
        $dates    = collect(range(0, self::DAYS - 1))->map(fn ($i) => $since->copy()->addDays($i)->toDateString());

        $fill  = fn ($map) => $dates->map(fn ($d) => (float) ($map[$d] ?? 0))->all();
        $fillN = fn ($map) => $dates->map(fn ($d) => isset($map[$d]) ? round((float) $map[$d], 1) : null)->all();
        $cum   = function (int $base, $map) use ($dates) {
            $run = $base;
            return $dates->map(function ($d) use (&$run, $map) { $run += (int) ($map[$d] ?? 0); return $run; })->all();
        };
        $delta = fn ($cur, $prev) => $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : null;

        // Query dasar yang terkunci ke pemilik
        $exams   = fn () => Exam::where('user_id', $uid);
        $subs    = fn () => Submission::whereIn('exam_id', Exam::where('user_id', $uid)->select('id'));
        $answers = fn () => StudentAnswer::whereIn('submission_id', $subs()->select('id'));
        $logs    = fn () => AiUsageLog::where('user_id', $uid);

        // ---- Ujian ----
        $examStatus  = $exams()->selectRaw('status, COUNT(*) AS c')->groupBy('status')->pluck('c', 'status');
        $examDaily   = $exams()->where('created_at', '>=', $since)->selectRaw('DATE(created_at) AS d, COUNT(*) AS c')->groupBy('d')->pluck('c', 'd');
        $examBefore  = $exams()->where('created_at', '<', $since)->count();

        // ---- Responden / pengiriman form ----
        $subDaily  = $subs()->where('created_at', '>=', $since)->selectRaw('DATE(created_at) AS d, COUNT(*) AS c')->groupBy('d')->pluck('c', 'd');
        $subBefore = $subs()->where('created_at', '<', $since)->count();
        $subPrev   = $subs()->where('created_at', '>=', $prevFrom)->where('created_at', '<', $since)->count();
        $subTotal  = $subs()->count();
        $subWeek   = $subs()->where('created_at', '>=', $weekAgo)->count();

        // ---- Analisis AI ----
        $ansStatus = $answers()->selectRaw('processing_status AS s, COUNT(*) AS c')->groupBy('processing_status')->pluck('c', 's');
        $ansTotal  = (int) $ansStatus->sum();

        // ---- Nilai ----
        $scored   = fn () => $answers()->whereNotNull('final_score');
        $scoreAvg = (float) ($scored()->avg('final_score') ?? 0);
        $scoreCnt = $scored()->count();
        $overridden = $answers()->where('is_overridden', true)->count();

        $b = $scored()->selectRaw("
            COALESCE(SUM(final_score < 20), 0) AS b0,
            COALESCE(SUM(final_score >= 20 AND final_score < 40), 0) AS b1,
            COALESCE(SUM(final_score >= 40 AND final_score < 60), 0) AS b2,
            COALESCE(SUM(final_score >= 60 AND final_score < 80), 0) AS b3,
            COALESCE(SUM(final_score >= 80), 0) AS b4")->first();
        $distVals = [(int) $b->b0, (int) $b->b1, (int) $b->b2, (int) $b->b3, (int) $b->b4];

        $scoreTrend = $scored()->where('processed_at', '>=', $since)
            ->selectRaw('DATE(processed_at) AS d, AVG(final_score) AS a')->groupBy('d')->pluck('a', 'd');

        // ---- Token AI (log lokal milik user) ----
        $agg = $logs()->selectRaw('
                COUNT(*) AS reqs,
                COALESCE(SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END), 0) AS ok,
                COALESCE(SUM(total_tokens), 0) AS tt,
                COALESCE(SUM(prompt_tokens), 0) AS pt,
                COALESCE(SUM(completion_tokens), 0) AS ct,
                COALESCE(SUM(CASE WHEN used_shared_key = 1 THEN total_tokens ELSE 0 END), 0) AS shared_tt,
                COALESCE(SUM(CASE WHEN success = 1 THEN answers_count ELSE 0 END), 0) AS ans,
                COALESCE(AVG(latency_ms), 0) AS lat')->first();

        $tokDaily = $logs()->where('created_at', '>=', $since)->selectRaw('DATE(created_at) AS d, SUM(total_tokens) AS t')->groupBy('d')->pluck('t', 'd');
        $tokPrev  = (int) $logs()->where('created_at', '>=', $prevFrom)->where('created_at', '<', $since)->sum('total_tokens');
        $reqs     = (int) $agg->reqs;

        // ---- Riwayat ujian terbaru ----
        $recent = $exams()->latest('updated_at')->limit(6)->get(['id', 'title', 'status', 'updated_at']);
        $ids    = $recent->pluck('id');
        $cnts   = Submission::whereIn('exam_id', $ids)->selectRaw('exam_id AS e, COUNT(*) AS c')->groupBy('exam_id')->pluck('c', 'e');
        $avgs   = StudentAnswer::join('submissions', 'submissions.id', '=', 'student_answers.submission_id')
            ->whereIn('submissions.exam_id', $ids)->whereNotNull('student_answers.final_score')
            ->groupBy('submissions.exam_id')->selectRaw('submissions.exam_id AS e, AVG(student_answers.final_score) AS a')->pluck('a', 'e');

        $history = $recent->map(fn ($e) => [
            'id'     => $e->id,
            'title'  => $e->title ?: 'Tanpa judul',
            'status' => $e->status,
            'subs'   => (int) ($cnts[$e->id] ?? 0),
            'avg'    => isset($avgs[$e->id]) ? round((float) $avgs[$e->id], 1) : null,
        ])->all();

        return view('stats', ['d' => [
            'name'   => $user->name,
            'days'   => self::DAYS,
            'labels' => $dates->map(fn ($x) => Carbon::parse($x)->format('j/n'))->all(),
            'exams'  => [
                'total'  => (int) $examStatus->sum(),
                'active' => (int) ($examStatus['active'] ?? 0),
                'draft'  => (int) ($examStatus['draft'] ?? 0),
                'closed' => (int) ($examStatus['closed'] ?? 0),
                'trend'  => $cum($examBefore, $examDaily),
            ],
            'subs' => [
                'total' => $subTotal,
                'week'  => $subWeek,
                'sum30' => (int) $subDaily->sum(),
                'delta' => $delta((int) $subDaily->sum(), $subPrev),
                'cum'   => $cum($subBefore, $subDaily),
                'daily' => $fill($subDaily),
            ],
            'ans' => [
                'total'   => $ansTotal,
                'success' => (int) ($ansStatus['success'] ?? 0),
                'failed'  => (int) ($ansStatus['failed'] ?? 0),
                'pending' => (int) ($ansStatus['pending'] ?? 0) + (int) ($ansStatus['processing'] ?? 0),
            ],
            'score' => [
                'avg'        => $scoreAvg,
                'count'      => $scoreCnt,
                'overridden' => $overridden,
                'dist'       => $distVals,
                'trend'      => $fillN($scoreTrend),
            ],
            'tok' => [
                'tt'        => (int) $agg->tt,
                'pt'        => (int) $agg->pt,
                'ct'        => (int) $agg->ct,
                'shared_tt' => (int) $agg->shared_tt,
                'sum30'     => (int) $tokDaily->sum(),
                'delta'     => $delta((int) $tokDaily->sum(), $tokPrev),
                'trend'     => $fill($tokDaily),
            ],
            'win' => [
                'reqs' => $reqs,
                'ok'   => (int) $agg->ok,
                'fail' => $reqs - (int) $agg->ok,
                'rate' => $reqs > 0 ? round($agg->ok / $reqs * 100, 1) : 0,
                'per'  => $agg->ans > 0 ? (int) round($agg->tt / $agg->ans) : 0,
                'lat'  => round($agg->lat / 1000, 1),
            ],
            'history' => $history,
        ]]);
    }
}