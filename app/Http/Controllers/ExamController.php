<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\StudentAnswer;
use App\Jobs\AnalyzeExamForm;
use App\Jobs\ProcessEssayGrading;
use App\Support\LiveState;
use App\Jobs\SyncExamResponses;
use App\Models\Exam;
use App\Services\FormAnalyzerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::where('user_id', auth()->id())->whereNull('gform_dismissed_at')->latest()->get();

        return view('dashboard', compact('exams'));
    }

    public function create()
    {
        return view('exam_create', [
            'connected' => (bool) auth()->user()->google_refresh_token,
        ]);
    }

    public function store(Request $request, FormAnalyzerService $analyzer)
    {
        $user = auth()->user();

        if (! $user->google_refresh_token) {
            return redirect()->route('google.index')
                ->with('status', 'Hubungkan akun Google dulu sebelum membuat penilaian.');
        }

        $data = $request->validate(['form_url' => 'required|string|max:500']);

        try {
            $formId = $analyzer->extractFormId($data['form_url']);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['form_url' => $e->getMessage()]);
        }

        $existing = Exam::where('user_id', $user->id)->where('gform_id', $formId)->first();

        if ($existing) {
            return redirect()->route('exams.edit', $existing)
                ->with('status', 'Form ini sudah terdaftar, jadi dibuka dari daftar penilaian Anda.');
        }

        $exam = Exam::create([
            'user_id'         => $user->id,
            'gform_url'       => trim($data['form_url']),
            'gform_id'        => $formId,
            'analysis_status' => 'pending',
            'status'          => 'draft',
            'webhook_token'   => Str::random(40),
        ]);

        AnalyzeExamForm::dispatch($exam);

        return redirect()->route('exams.edit', $exam)
            ->with('status', 'Penilaian dibuat. Form sedang dianalisis.');
    }

    public function edit(Exam $exam)
    {
        $this->authorizeExam($exam);

        $questions = $exam->questions()->orderBy('order')->get();
        $graded = StudentAnswer::whereIn('question_id', $questions->whereIn('gform_item_type', Question::TEXT_TYPES)->pluck('id'))
            ->where('processing_status', 'success')
            ->selectRaw('question_id, COUNT(*) AS c')->groupBy('question_id')->pluck('c', 'question_id');

        return view('exam_edit', compact('exam', 'questions', 'graded'));
    }

    public function reanalyze(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        if ($exam->status === 'active') {
            if ($request->boolean('close_and_reanalyze')) {
                $exam->update(['status' => 'closed']);
            } else {
                return back()->withErrors(['status' => 'Tutup status penilaian terlebih dahulu sebelum melakukan analisis ulang.']);
            }
        }

        $exam->update(['analysis_status' => 'pending', 'analysis_error' => null]);

        AnalyzeExamForm::dispatch($exam);

        $msg = $exam->wasChanged('status')
            ? 'Penilaian ditutup dan analisis ulang formulir dimulai.'
            : 'Analisis ulang dimulai.';

        return back()->with('status', $msg);
    }

    public function saveAll(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        $data = $request->validate([
            'roles'   => 'sometimes|array',
            'roles.*' => 'in:identity,stimulus,main',
            'modes'   => 'sometimes|array',
            'modes.*' => 'in:scoring,non_scoring',
            'keys'    => 'sometimes|array',
            'keys.*'  => 'nullable|string|max:5000',
        ]);

        $questions = $exam->questions()->get();
        $roles = $data['roles'] ?? [];
        $modes = $data['modes'] ?? [];
        $keys  = $data['keys'] ?? [];

        $errors = [];
        foreach ($questions as $q) {
            if (($roles[$q->id] ?? null) === 'identity' && $q->field_type !== 'identity'
                && ! in_array($q->gform_item_type, Question::IDENTITY_TYPES, true)) {
                $errors[] = 'Kolom "' . Str::limit($q->question_text, 40) . '" tidak cocok dijadikan Identitas Responden.';
            }
        }
        if ($errors) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $errors[0]], 422)
                : back()->withErrors(['roles' => $errors]);
        }

        $norm         = fn ($v) => trim(str_replace("\r\n", "\n", (string) $v)) ?: null;
        $plan         = [];
        $startScoring = false;

        foreach ($questions as $q) {
            $role   = $roles[$q->id] ?? $q->field_type;
            $isText   = in_array($q->gform_item_type, Question::TEXT_TYPES, true);
            $idleMode = $role === 'identity' ? ($q->gform_item_type === 'paragraph' ? 'non_scoring' : null) : 'scoring';
            $mode     = $isText ? (($modes[$q->id] ?? $q->grading_mode) ?: $idleMode) : null;

            $changes = [];
            if ($role !== $q->field_type) { $changes['field_type'] = $role; $changes['role_detail'] = null; }
            if ($mode !== $q->grading_mode) { $changes['grading_mode'] = $mode; }
            if ($isText && array_key_exists($q->id, $keys) && $norm($keys[$q->id]) !== $norm($q->answer_key)) {
                $changes['answer_key'] = $norm($keys[$q->id]);
            }

            if ($changes) {
                $plan[$q->id] = $changes;
                if (($changes['grading_mode'] ?? null) === 'scoring') { $startScoring = true; }
            }
        }

        $changed = $plan !== [];

        if ($changed && $exam->status === 'active') {
            return $this->saveRefusal($request, 'Perubahan tidak dapat disimpan saat penilaian aktif. Tutup status penilaian terlebih dahulu sebelum melakukan perubahan.', 409, ['locked' => true]);
        }

        $clearIds = [];
        foreach ($plan as $id => $c) {
            if (($c['grading_mode'] ?? null) === 'non_scoring' && $questions->firstWhere('id', $id)->grading_mode === 'scoring') {
                $clearIds[] = $id;
            }
        }
        $affected = $clearIds ? StudentAnswer::whereIn('question_id', $clearIds)->where('processing_status', 'success')->count() : 0;

        if ($affected > 0 && ! $request->boolean('confirm_clear')) {
            return $this->saveRefusal($request, "Mengubah ke \"Tidak dinilai\" akan menghapus $affected nilai terkait secara permanen.", 409, ['needs_confirm' => true, 'affected' => $affected]);
        }

        if ($changed) {
            DB::transaction(function () use ($questions, $plan, $clearIds) {
                foreach ($questions as $q) {
                    if (isset($plan[$q->id])) { $q->update($plan[$q->id]); }
                }

                if ($clearIds) {
                    StudentAnswer::whereIn('question_id', $clearIds)->update([
                        'score_content' => null, 'score_logic' => null, 'score_structure' => null, 'final_score' => null,
                        'ai_category' => null, 'ai_analysis' => null, 'is_overridden' => false, 'override_note' => null,
                        'overridden_at' => null, 'overridden_by' => null, 'ai_original' => null,
                        'processing_status' => 'pending', 'attempts' => 0, 'last_error' => null, 'processed_at' => null,
                    ]);
                }
            });

            if ($startScoring) {
                $exam->update(['last_synced_at' => null]);
                ProcessEssayGrading::dispatch();
            }
        }

        $activated = false;

        if ($request->boolean('activate')) {
            if ($error = $this->activationError($exam->fresh())) {
                return $request->expectsJson()
                    ? response()->json(['ok' => false, 'changed' => $changed, 'message' => $error], 422)
                    : back()->withErrors(['status' => $error]);
            }

            $this->doActivate($exam);
            $activated = true;
        }

        $message = $activated ? 'Penilaian aktif.' : ($changed ? 'Perubahan disimpan.' : 'Tidak ada perubahan.');

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'changed' => $changed, 'activated' => $activated, 'message' => $message])
            : back()->with('status', $message);
    }

    public function sync(Exam $exam)
    {
        $this->authorizeExam($exam);

        if ($exam->status !== 'active') {
            return back()->withErrors(['status' => 'Aktifkan penilaian dulu sebelum mengambil jawaban.']);
        }

        SyncExamResponses::dispatch($exam);

        return back()->with('status', 'Sedang mengambil jawaban terbaru. Muat ulang halaman ini beberapa detik lagi.');
    }

    public function activate(Exam $exam)
    {
        $this->authorizeExam($exam);

        if ($error = $this->activationError($exam)) {
            return back()->withErrors(['status' => $error]);
        }

        $this->doActivate($exam);

        return back()->with('status', 'Penilaian aktif. Jawaban mulai diambil dari Google Form dan seterusnya diambil otomatis setiap menit.');
    }

    public function close(Exam $exam)
    {
        $this->authorizeExam($exam);

        $exam->update(['status' => 'closed']);

        return back()->with('status', 'Penilaian ditutup.');
    }

    public function destroy(Exam $exam)
    {
        $this->authorizeExam($exam);

        DB::transaction(fn () => $exam->delete());

        return redirect()->route('dashboard')->with('status', 'Penilaian dan seluruh datanya sudah dihapus permanen.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1|max:500',
            'ids.*' => 'integer',
        ]);

        // Hanya milik pengguna ini; id milik orang lain diabaikan
        $exams = Exam::where('user_id', auth()->id())->whereIn('id', $data['ids'])->get();

        DB::transaction(fn () => $exams->each->delete());

        $n = $exams->count();

        return redirect()->route('dashboard')->with('status', $n > 0
            ? "$n penilaian dan seluruh datanya sudah dihapus permanen."
            : 'Tidak ada penilaian yang dihapus.');
    }

    public function live(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        if ($exam->status === 'active' && $exam->analysis_status === 'success'
            && Cache::add("live-sync:{$exam->id}", 1, 15)) {
            SyncExamResponses::dispatch($exam);
        }

        $data     = LiveState::snapshot($exam);
        $response = response()->json($data)->setEtag($data['version']);
        $response->headers->set('Cache-Control', 'no-cache, private');
        $response->isNotModified($request);

        return $response;
    }

    private function saveRefusal(Request $request, string $message, int $status, array $extra = [])
    {
        return $request->expectsJson()
            ? response()->json(['ok' => false, 'message' => $message] + $extra, $status)
            : back()->withErrors(['status' => $message]);
    }

    private function authorizeExam(Exam $exam): void
    {
        abort_unless($exam->user_id === auth()->id(), 403);
    }

    private function activationError(Exam $exam): ?string
    {
        if ($exam->analysis_status !== 'success') {
            return 'Formulir belum berhasil dianalisis.';
        }

        if (! $exam->questions()->scoring()->exists()) {
            return 'Belum ada pertanyaan Paragraf atau Jawaban singkat dengan mode "Dinilai".';
        }

        $noKey = $exam->questions()->scoring()->where('gform_item_type', 'short_answer')
            ->where(fn ($q) => $q->whereNull('answer_key')->orWhere('answer_key', ''))
            ->first();

        if ($noKey) {
            return 'Pertanyaan Jawaban singkat "' . Str::limit($noKey->question_text, 40) . '" berstatus "Dinilai" tetapi kunci jawabannya belum diisi.';
        }

        try {
            app(\App\Services\GoogleClient::class)->getForm($exam->user, $exam->gform_id);
        } catch (\App\Services\FormGoneException $e) {
            $exam->update(['gform_deleted_at' => now()]);

            return 'Google Form ini sudah dihapus atau tidak dapat diakses, sehingga penilaian tidak bisa diaktifkan.';
        } catch (\Throwable $e) {
            // Error lain (jaringan, dll.) tidak dianggap form terhapus; ditangani oleh proses sinkronisasi
        }

        return null;
    }

    private function doActivate(Exam $exam): void
    {
        $exam->update(['status' => 'active']);
        SyncExamResponses::dispatch($exam);
    }
}