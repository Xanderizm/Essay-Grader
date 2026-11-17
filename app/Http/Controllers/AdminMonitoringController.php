<?php

namespace App\Http\Controllers;

use App\Models\AiApiKey;
use App\Models\AiUsageLog;
use App\Models\Exam;
use App\Models\StudentAnswer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class AdminMonitoringController extends Controller
{
    private const DAYS = 30;

    public function index(Request $request)
    {
        $since   = now()->subDays(self::DAYS - 1)->startOfDay();
        $weekAgo = now()->subDays(7);
        $dates   = collect(range(0, self::DAYS - 1))->map(fn ($i) => $since->copy()->addDays($i)->toDateString());
        $fill    = fn ($map) => $dates->map(fn ($d) => isset($map[$d]) ? (float) $map[$d] : 0)->all();
        $delta   = fn ($cur, $prev) => $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : null;
        $prevFrom = $since->copy()->subDays(self::DAYS);

        // ---- Sistem ----
        $examStatus = Exam::selectRaw('status, COUNT(*) AS c')->groupBy('status')->pluck('c', 'status');
        $ansStatus  = StudentAnswer::selectRaw('processing_status AS s, COUNT(*) AS c')->groupBy('processing_status')->pluck('c', 's');

        $signups = User::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) AS d, COUNT(*) AS c')->groupBy('d')->pluck('c', 'd');

        $proc = StudentAnswer::where('processing_status', 'success')->where('processed_at', '>=', $since)
            ->selectRaw('DATE(processed_at) AS d, COUNT(*) AS c')->groupBy('d')->pluck('c', 'd');
        $procPrev = StudentAnswer::where('processing_status', 'success')
            ->where('processed_at', '>=', $prevFrom)->where('processed_at', '<', $since)->count();

        $activeIds = AiUsageLog::where('created_at', '>=', $weekAgo)->whereNotNull('user_id')->distinct()->pluck('user_id')
            ->merge(Exam::where('updated_at', '>=', $weekAgo)->distinct()->pluck('user_id'))->unique();

        // ---- AI ----
        $agg = AiUsageLog::selectRaw("
                COUNT(*) AS reqs,
                COALESCE(SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END), 0) AS ok,
                COALESCE(SUM(total_tokens), 0) AS tt,
                COALESCE(SUM(CASE WHEN used_shared_key = 1 THEN total_tokens ELSE 0 END), 0) AS shared_tt,
                COALESCE(SUM(CASE WHEN success = 1 THEN answers_count ELSE 0 END), 0) AS ans,
                COALESCE(AVG(latency_ms), 0) AS lat")->first();

        $tokTrend = AiUsageLog::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) AS d, SUM(total_tokens) AS t')->groupBy('d')->pluck('t', 'd');
        $tokPrev = (int) AiUsageLog::where('created_at', '>=', $prevFrom)->where('created_at', '<', $since)->sum('total_tokens');

        $topErrors = AiUsageLog::where('success', false)->where('created_at', '>=', $weekAgo)
            ->selectRaw("COALESCE(error_code, 'unknown') AS code, COUNT(*) AS c")
            ->groupBy('code')->orderByDesc('c')->limit(5)->get();

        $peakDate = $tokTrend->isNotEmpty() ? $tokTrend->sortDesc()->keys()->first() : null;
        $gauge = [
            'normal' => $this->gaugeStats(AiUsageLog::query()->where('created_at', '>=', $since)),
            'peak'   => $peakDate
                ? $this->gaugeStats(AiUsageLog::query()->whereDate('created_at', $peakDate)) + ['date' => Carbon::parse($peakDate)->format('j/n')]
                : $this->gaugeStats(AiUsageLog::query()->whereRaw('1 = 0')) + ['date' => '–'],
            'total'  => $this->gaugeStats(AiUsageLog::query()),
        ];

        $reqs = (int) $agg->reqs;

        // ---- Kunci API (isi kunci tidak pernah dibaca) ----
        $keyRows = AiApiKey::orderByDesc('is_active')->orderByDesc('last_used_at')
            ->get(['id', 'label', 'provider_name', 'is_active', 'user_id', 'cooldown_until', 'daily_usage_count', 'usage_date', 'last_used_at', 'created_at']);

        $keys = [
            'total'    => $keyRows->count(),
            'active'   => $keyRows->where('is_active', true)->count(),
            'shared'   => $keyRows->whereNull('user_id')->count(),
            'personal' => $keyRows->whereNotNull('user_id')->count(),
            'cooling'  => $keyRows->filter(fn ($k) => $k->is_active && $k->cooldown_until && $k->cooldown_until->isFuture())->count(),
            'list'     => $keyRows->values()->map(function ($k) {
                $cool = $k->cooldown_until && $k->cooldown_until->isFuture();
                [$status, $state] = ! $k->is_active ? ['Nonaktif', 'bad'] : ($cool ? ['Cooldown', 'warn'] : ['Aktif', 'ok']);

                return [
                    'id'       => $k->id,
                    'label'    => $k->label ?: ('Key #' . $k->id),
                    'provider' => ucfirst((string) $k->provider_name) . ' API',
                    'mask'     => '**** ' . str_pad((string) $k->id, 4, '0', STR_PAD_LEFT),
                    'scope'    => $k->user_id ? 'Pribadi' : 'Bersama',
                    'status'   => $status,
                    'state'    => $state,
                    'usage'    => ($k->usage_date && $k->usage_date->isToday()) ? (int) $k->daily_usage_count : 0,
                    'last'     => $k->last_used_at ? $k->last_used_at->diffForHumans() : 'belum pernah',
                    'cool'     => $cool ? $k->cooldown_until->diffForHumans() : null,
                    'since'    => $k->created_at?->format('d M Y'),
                ];
            })->all(),
        ];

        // ---- Tabel user: metadata saja ----
        $users = User::query()
            ->select(['users.id', 'users.email', 'users.is_admin', 'users.email_verified_at', 'users.deletion_requested_at', 'users.created_at'])
            ->selectSub(Exam::query()->selectRaw('COUNT(*)')->whereColumn('exams.user_id', 'users.id'), 'exams_count')
            ->selectSub(Exam::query()->selectRaw('COUNT(*)')->whereColumn('exams.user_id', 'users.id')->where('status', 'active'), 'active_exams')
            ->selectSub(Exam::query()->selectRaw('MAX(updated_at)')->whereColumn('exams.user_id', 'users.id'), 'last_exam_at')
            ->selectSub(AiUsageLog::query()->selectRaw('COALESCE(SUM(total_tokens), 0)')->whereColumn('ai_usage_logs.user_id', 'users.id'), 'tokens_total')
            ->selectSub(AiUsageLog::query()->selectRaw('MAX(created_at)')->whereColumn('ai_usage_logs.user_id', 'users.id'), 'last_ai_at')
            ->orderByDesc('users.created_at')
            ->paginate(15)
            ->withQueryString();

        $users->getCollection()->transform(function ($u) use ($weekAgo) {
            $last = collect([$u->last_ai_at, $u->last_exam_at])->filter()
                ->map(fn ($v) => Carbon::parse($v))->sortDesc()->first();

            $u->masked        = $this->maskEmail($u->email);
            $u->last_activity = $last;
            $u->state = match (true) {
                filled($u->deletion_requested_at)                         => ['Dijadwalkan hapus', 'bad'],
                ! $u->email_verified_at                                   => ['Belum verifikasi', 'warn'],
                $u->active_exams > 0 || ($last && $last->gt($weekAgo))    => ['Aktif', 'ok'],
                default                                                   => ['Idle', ''],
            };

            return $u;
        });

        return view('admin_monitoring', ['d' => [
            'labels' => $dates->map(fn ($x) => Carbon::parse($x)->format('j/n'))->all(),
            'greet'  => explode(' ', trim($request->user()->name))[0] ?? 'Admin',
            'users' => [
                'total'    => User::count(),
                'verified' => User::whereNotNull('email_verified_at')->count(),
                'week'     => User::where('created_at', '>=', $weekAgo)->count(),
                'admins'   => User::where('is_admin', true)->count(),
                'active7'  => $activeIds->count(),
                'signups'  => $fill($signups),
            ],
            'exams' => [
                'total'  => (int) $examStatus->sum(),
                'active' => (int) ($examStatus['active'] ?? 0),
                'draft'  => (int) ($examStatus['draft'] ?? 0),
                'closed' => (int) ($examStatus['closed'] ?? 0),
            ],
            'ans' => [
                'total'   => (int) $ansStatus->sum(),
                'success' => (int) ($ansStatus['success'] ?? 0),
                'failed'  => (int) ($ansStatus['failed'] ?? 0),
                'pending' => (int) ($ansStatus['pending'] ?? 0) + (int) ($ansStatus['processing'] ?? 0),
                'logged'  => (int) $agg->ans,
                'trend'   => $fill($proc),
                'sum30'   => (int) $proc->sum(),
                'delta'   => $delta((int) $proc->sum(), $procPrev),
            ],
            'ai' => [
                'reqs'      => $reqs,
                'ok'        => (int) $agg->ok,
                'fail'      => $reqs - (int) $agg->ok,
                'err_rate'  => $reqs > 0 ? round(($reqs - $agg->ok) / $reqs * 100, 1) : 0,
                'tt'        => (int) $agg->tt,
                'shared_tt' => (int) $agg->shared_tt,
                'lat'       => (int) $agg->lat,
                'trend'     => $fill($tokTrend),
                'sum30'     => (int) $tokTrend->sum(),
                'delta'     => $delta((int) $tokTrend->sum(), $tokPrev),
                'errors'    => $topErrors,
            ],
            'gauge'    => $gauge,
            'keys'     => $keys,
            'userRows' => $users,
        ]]);
    }

    public function storeUser(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:10|max:128',
            'verified' => 'nullable|boolean',
        ], [
            'name.required'     => 'Nama wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 10 karakter.',
        ]);

        $verified = $request->boolean('verified');

        $user = new User();
        $user->forceFill([
            'name'              => trim($data['name']),
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'password_set'      => true,
            'email_verified_at' => $verified ? now() : null,
        ])->save();

        if (! $verified) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->to(route('admin.monitoring') . '#tambah-user')
            ->with('status', 'Akun ' . $this->maskEmail($user->email) . ' berhasil dibuat.');
    }

    private function gaugeStats($q): array
    {
        $r = $q->selectRaw('COUNT(*) AS reqs, COALESCE(SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END), 0) AS ok, COALESCE(SUM(total_tokens), 0) AS tt')->first();
        $reqs = (int) $r->reqs;
        $ok   = (int) $r->ok;

        return [
            'reqs'   => $reqs,
            'tt'     => (int) $r->tt,
            'health' => $reqs > 0 ? round($ok / $reqs * 100, 1) : 0,
            'err'    => $reqs > 0 ? round(($reqs - $ok) / $reqs * 100, 1) : 0,
        ];
    }

    private function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '—';
        }

        [$name, $domain] = explode('@', $email, 2);
        $visible = mb_substr($name, 0, min(2, mb_strlen($name)));

        return $visible . str_repeat('*', max(3, mb_strlen($name) - mb_strlen($visible))) . '@' . $domain;
    }
}