<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEssayGrading;
use App\Models\Exam;
use App\Models\StudentAnswer;
use App\Services\EssayGraderService;
use App\Support\LiveState;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResultController extends Controller
{
    private const NEAR_BOUNDARY = 3;

    public function show(Request $request, Exam $exam, EssayGraderService $grader)
    {
        $this->authorizeExam($exam);

        $questions = $this->essayQuestions($exam);
        $all       = $request->boolean('semua');

        $identityFields = $exam->questions()->identityColumns()->orderBy('order')->get(['id', 'question_text']);
        $firstId = $identityFields->first()?->id;
        $sort    = (string) $request->query('urut', $firstId ? (string) $firstId : 'nilai');
        $colId   = (int) $request->query('kolom', $firstId ?? 0);
        $search  = trim((string) $request->query('cari', ''));

        [$rows, $hidden] = $this->buildRows($exam, $questions, $grader, $all);

        $labels = $questions->values()->mapWithKeys(fn ($q, $i) => [
            $q->id => ($q->prompt_kind === 'instruction' ? 'Perintah' : 'Pertanyaan') . ' ' . ($i + 1),
        ]);
        $chartData = [
            'items' => $questions->values()->map(fn ($q) => [
                'id'     => $q->id,
                'label'  => $labels[$q->id],
                'scores' => $rows->map(fn ($r) => $r['answers']->get($q->id))
                    ->filter(fn ($a) => $a && $a->processing_status === 'success' && $a->final_score !== null)
                    ->map(fn ($a) => round((float) $a->final_score, 1))->values()->all(),
            ])->all()
        ];
        $live = LiveState::snapshot($exam);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(fn ($r) => collect($r['submission']->identity ?? [])
                ->contains(fn ($v) => str_contains(mb_strtolower((string) $v), $needle)))->values();
        }

        if ($sort === 'nilai') {
            $rows = $rows->sortByDesc(fn ($r) => $r['average'] ?? -1)->values();
        } else {
            $rows = $rows->sortBy(fn ($r) => mb_strtolower((string) (($r['submission']->identity ?? [])[$sort] ?? '')), SORT_NATURAL)->values();
        }

        $counts = StudentAnswer::whereIn('question_id', $questions->pluck('id'))
            ->selectRaw('processing_status, COUNT(*) AS c')
            ->groupBy('processing_status')
            ->pluck('c', 'processing_status');

        $overridden = StudentAnswer::whereIn('question_id', $questions->pluck('id'))
            ->where('is_overridden', true)
            ->count();

        $distribution = collect(array_keys(config('grading.categories')))
            ->mapWithKeys(fn ($label) => [$label => $rows->where('category', $label)->count()]);

        $classAverage = $rows->whereNotNull('average')->avg('average');

        $near = fn (float $score) => $this->nearBoundary($score);

        return view('exam', compact(
            'exam', 'questions', 'rows', 'hidden', 'all', 'sort',
            'counts', 'distribution', 'classAverage', 'near', 'overridden',
            'identityFields', 'colId', 'search', 'labels', 'chartData', 'live'
        ));
    }

    public function export(Request $request, Exam $exam, EssayGraderService $grader)
    {
        $this->authorizeExam($exam);

        if (! $exam->submissions()->exists()) {
            return back()->with('status', 'Belum ada jawaban yang bisa diunduh.');
        }

        try {
            $questions = $this->essayQuestions($exam);
            [$rows]    = $this->buildRows($exam, $questions, $grader, $request->boolean('semua'));
            $rows      = $rows->sortBy(fn ($r) => $r['submission']->id)->values();
            $identity  = $exam->questions()->identityColumns()->orderBy('order')->get(['id', 'question_text']);

            $out = fopen('php://temp/maxmemory:8388608', 'w+');
            fwrite($out, "\xEF\xBB\xBF");
            fwrite($out, "sep=;\n");

            $header = $identity->pluck('question_text')->all();
            foreach ($questions->values() as $i => $q) {
                $label = ($q->prompt_kind === 'instruction' ? 'Perintah' : 'Pertanyaan') . ' ' . ($i + 1);
                array_push($header, "$label Konten", "$label Logika", "$label Struktur", "$label Nilai", "$label Kategori", "$label Analisis", "$label Disesuaikan", "$label Catatan");
            }
            array_push($header, 'Rata-rata', 'Kategori Akhir', 'Status');
            fputcsv($out, $header, ';', '"', '\\');

            foreach ($rows as $row) {
                $s    = $row['submission'];
                $line = $identity->map(fn ($f) => self::safe((string) (($s->identity ?? [])[$f->id] ?? '')))->all();

                foreach ($questions as $q) {
                    $a = $row['answers']->get($q->id);

                    if ($a && $a->processing_status === 'success' && $a->final_score !== null) {
                        array_push($line, $a->score_content, $a->score_logic, $a->score_structure,
                            number_format((float) $a->final_score, 2, ',', ''), $a->ai_category,
                            self::safe($a->ai_analysis), $a->is_overridden ? 'Ya' : 'Tidak', self::safe($a->override_note));
                    } else {
                        array_push($line, '', '', '', '', $a ? $a->processing_status : '', '', '', '');
                    }
                }

                array_push($line,
                    $row['average'] !== null ? number_format((float) $row['average'], 2, ',', '') : '',
                    $row['category'] ?? '',
                    $row['average'] === null ? 'Belum dinilai' : ($row['complete'] ? 'Lengkap' : 'Sebagian'));

                fputcsv($out, $line, ';', '"', '\\');
            }

            rewind($out);
            $csv = stream_get_contents($out);
            fclose($out);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('status', 'Gagal membuat file unduhan. Detailnya tercatat di log aplikasi.');
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . Str::slug($exam->title ?: 'penilaian') . '-nilai.csv"',
        ]);
    }

    public function retryFailed(Exam $exam)
    {
        $this->authorizeExam($exam);

        $count = StudentAnswer::whereIn('question_id', $this->essayQuestions($exam)->pluck('id'))
            ->where('processing_status', 'failed')
            ->update(['processing_status' => 'pending', 'attempts' => 0, 'last_error' => null]);

        if ($count > 0) {
            ProcessEssayGrading::dispatch();
        }

        return back()->with('status', $count > 0
            ? "$count jawaban yang gagal dijadwalkan ulang."
            : 'Tidak ada jawaban yang gagal.');
    }

    private function buildRows(Exam $exam, Collection $questions, EssayGraderService $grader, bool $all): array
    {
        $submissions = $exam->submissions()->with('answers')->orderBy('id')->get();

        if ($all) {
            $selected = $submissions;
        } else {
            $latestPerNim = $submissions
                ->filter(fn ($s) => filled($s->identity_key))
                ->groupBy(fn ($s) => $s->identity_key)
                ->map(fn ($group) => $group
                    ->sortByDesc(fn ($s) => ($s->submitted_at?->timestamp ?? 0) * 1000000 + $s->id)
                    ->first());

            $selected = $latestPerNim->concat($submissions->reject(fn ($s) => filled($s->identity_key)));
        }

        $hidden = $submissions->count() - $selected->count();

        $rows = $selected->map(function ($s) use ($questions, $grader) {
            $byQuestion = $s->answers->keyBy('question_id');
            $scores     = [];

            foreach ($questions as $q) {
                $a = $byQuestion->get($q->id);

                if ($a && $a->processing_status === 'success' && $a->final_score !== null) {
                    $scores[] = (float) $a->final_score;
                }
            }

            $average = $scores ? round(array_sum($scores) / count($scores), 2) : null;

            return [
                'submission' => $s,
                'answers'    => $byQuestion,
                'average'    => $average,
                'complete'   => count($scores) === $questions->count(),
                'category'   => $average !== null ? $grader->categoryFor($average) : null,
                'near'       => $average !== null && $this->nearBoundary($average),
            ];
        });

        return [$rows, $hidden];
    }

    private function essayQuestions(Exam $exam): Collection
    {
        return $exam->questions()->scoring()->orderBy('order')->get();  
    }

    private function nearBoundary(float $score): bool
    {
        foreach (config('grading.categories') as $min) {
            if ($min > 0 && abs($score - $min) <= self::NEAR_BOUNDARY) {
                return true;
            }
        }

        return false;
    }

    private static function safe($value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    private function authorizeExam(Exam $exam): void
    {
        abort_unless($exam->user_id === auth()->id(), 403);
    }
}