@extends('layout')
@section('title', 'Kelola ' . ($exam->title ?? 'Penilaian'))
@section('content')
@include('shape-grid', ['scope' => 'box'])
@push('styles')
    <style>
        .badge { white-space: nowrap; }
        .badge-small { font-size: 10px; letter-spacing: -.2px; }
        .type-short_answer { background: #cfe2ff; }
        .type-paragraph { background: #c4f55d; }
        .type-multiple_choice { background: #ffe27a; }
        .type-checkbox { background: #ffc2e0; }
        .type-dropdown { background: #d9ccff; }
        .type-file_upload { background: #e0e0dc; }
        .type-linear_scale { background: #b9efe0; }
        .type-rating { background: #ffc9a3; }
        .type-multiple_choice_grid { background: #a8d8f0; }
        .type-checkbox_grid { background: #f0c4f5; }
        .type-date { background: #e6d3b3; }
        .type-time { background: #c1c8f5; }

        .dlg { border: 0; padding: 0; background: transparent; max-width: 480px; width: calc(100% - 32px); }
        .dlg::backdrop { background: rgba(27, 27, 27, .5); }
        .dlg-box { background: #fff; border: 1.5px solid var(--ink); border-radius: 18px; box-shadow: 0 4px 0 var(--ink); padding: 22px; }
        .dlg-head { display: grid; grid-template-columns: auto 1fr auto; gap: 12px; align-items: start; margin-bottom: 16px; }
        .dlg-head h3 { margin: 0 0 4px; font-size: 19px; }
        .dlg-head p { margin: 0; }
        .dlg-icon { width: 36px; height: 36px; border-radius: 50%; background: var(--amber); border: 1.5px solid var(--ink); display: grid; place-items: center; font: 700 18px var(--display); }
        .dlg-x { background: none; color: var(--ink); border: 0; padding: 4px 8px; font-size: 22px; line-height: 1; box-shadow: none; }
        .dlg-x:hover { background: var(--mist); box-shadow: none; transform: none; }
        .dlg-options { display: grid; gap: 10px; }
        .dlg-opt { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; width: 100%; text-align: left; padding: 12px 14px; background: #fff; color: var(--ink); font-weight: 400; line-height: 1.4; border-radius: 12px; }
        .dlg-opt b { font: 700 14px var(--display); }
        .dlg-opt span { font-size: 12px; color: var(--muted); }
        .dlg-opt-primary { background: var(--lime); }
        .dlg-opt-primary span { color: var(--ink); }
        .dlg-opt-danger { border-color: #9b1c1c; }
        .dlg-opt-danger b { color: #9b1c1c; }

        .dlg-icon-danger { background: var(--coral); }
        .dlg-warn { background: #fff5f2; border: 1.5px solid #9b1c1c; border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; }
        .dlg-warn p { margin: 0 0 6px; }
        .dlg-warn ul { margin: 0 0 8px; padding-left: 18px; }
        .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }        

        th select { min-width: 140px; font-weight: 700; }
        th { text-align: center !important; }

        .dlg-center .dlg-box { text-align: center; }
        .dlg-icon-lg { width: 52px; height: 52px; margin: 0 auto 12px; font-size: 24px; }
        .dlg-center h3 { margin: 0 0 8px; }
        .dlg-center p { margin: 0 0 16px; }
    </style>
@endpush
@php
    $pending       = in_array($exam->analysis_status, ['pending', 'analyzing'], true);
    $typeLabels    = \App\Models\Question::TYPE_LABELS;
    $paragraphs    = $questions->where('gform_item_type', 'paragraph');
@endphp

<p><a href="{{ route('dashboard') }}">&larr; Kembali</a></p>
<h2>{{ $exam->title ?? 'Penilaian baru' }}</h2>
<div id="live-root" data-live-url="{{ route('exams.live', $exam) }}" data-analysis="{{ $exam->analysis_status }}"></div>
@if($exam->analysis_status === 'success')
    <p><a class="btn" href="{{ route('exams.show', $exam) }}">Lihat Tabel Nilai</a></p>
@endif

@if($errors->any())
    <div class="card" style="border-left:4px solid #991b1b; margin-bottom:16px">
        @foreach($errors->all() as $e)<div class="error">{{ $e }}</div>@endforeach
    </div>
@endif

<div class="card" style="margin-bottom:16px">
    <p class="muted" style="margin-top:0">Form: {{ $exam->gform_url ?: $exam->gform_id }}</p>

    <p>
        Analisis formulir:
        @if($pending)
            <span class="badge warn">Sedang menganalisis...</span>
        @elseif($exam->analysis_status === 'success')
            <span class="badge ok">Analisis berhasil</span>
            <span class="muted">{{ $exam->analyzed_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</span>
        @else
            <span class="badge bad">Analisis gagal</span>
        @endif
    </p>

    @if($pending)
        <p class="muted">Halaman ini diperbarui otomatis. Mohon tunggu sebentar.</p>
    @endif

    @if($exam->analysis_status === 'failed')
        <p class="error">{{ \App\Support\Friendly::error($exam->analysis_error) }}</p>
    @endif

    <p>
        Status penilaian:
        @if($exam->status === 'active')
            <span class="badge ok">Aktif</span>
        @elseif($exam->status === 'closed')
            <span class="badge bad">Ditutup</span>
        @else
            <span class="badge warn">Draf</span>
        @endif
    </p>

    <div class="row-actions">
        @if($exam->status === 'active')
            <form id="close-form" method="POST" action="{{ route('exams.close', $exam) }}">
                @csrf
                <button type="submit">Tutup penilaian</button>
            </form>
        @elseif($exam->analysis_status === 'success')
            <form id="activate-form" method="POST" action="{{ route('exams.activate', $exam) }}">
                @csrf
                <button type="submit">{{ $exam->status === 'closed' ? 'Aktifkan kembali' : 'Aktifkan penilaian' }}</button>
            </form>
        @endif

        @if($exam->analysis_status === 'success')
            <button type="submit" form="exam-form" id="save-btn" class="btn-lime" hidden>Simpan perubahan</button>
            <span id="save-note" class="muted" role="status"></span>
        @endif

        @unless($pending)
            <form id="reanalyze-form" method="POST" action="{{ route('exams.reanalyze', $exam) }}" data-active="{{ $exam->status === 'active' ? 1 : 0 }}">
                @csrf
                <button type="submit">Analisis ulang</button>
            </form>
        @endunless

        <form id="delete-form" method="POST" action="{{ route('exams.destroy', $exam) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="danger">Hapus penilaian</button>
        </form>
    </div>
</div>

@if($exam->status === 'active')
    <div class="card" style="margin-bottom:16px">
        <h3 style="margin-top:0">Pengambilan Jawaban dari Google Form</h3>
        <p>Jawaban masuk: <b data-live="submissions">{{ $exam->submissions()->count() }}</b> pengiriman. Terakhir diambil: <b data-live="synced">{{ $exam->last_synced_at ? \Illuminate\Support\Carbon::parse($exam->last_synced_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') : 'belum pernah' }}</b></p>
        @if($exam->sync_error)
            <p class="error" data-live="error" @if(! $exam->sync_error) hidden @endif>{{ \App\Support\Friendly::error($exam->sync_error) }}</p>
        @endif
        <p class="muted">Jumlah jawaban diperbarui otomatis tiap 5 detik.</p>
        <form method="POST" action="{{ route('exams.sync', $exam) }}">
            @csrf
            <button type="submit" data-ajax>Ambil jawaban sekarang</button>
        </form>
    </div>
@endif
@if($exam->analysis_status === 'success')
    <form id="exam-form" method="POST" action="{{ route('exams.fields', $exam) }}" data-exam="{{ $exam->id }}" data-active="{{ $exam->status === 'active' ? 1 : 0 }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="activate" id="activate-flag" value="0">
    </form>

    <div id="draft-banner" class="flash" hidden>
        Draf perubahan yang belum disimpan dipulihkan.
        <button type="button" id="draft-discard" class="btn-outline" style="margin-left:8px">Buang draf</button>
    </div>

    <div class="card" style="margin-bottom:16px; overflow-x:auto">
        <h3 style="margin-top:0">Struktur Formulir</h3>
        <p class="muted">Jenis kolom dibaca otomatis dari Google Form dan tidak dapat diubah. Peran kolom ditentukan otomatis dan bisa Anda ubah.</p>
        <table>
            <tr><th>No</th><th>Judul/Pertanyaan</th><th>Tipe Kolom</th></tr>
            @foreach($questions as $i => $q)
                <tr>
                    <td style="text-align: center;">{{ $i + 1 }}</td>
                    <td>
                        @if(in_array($q->gform_item_type, ['multiple_choice_grid', 'checkbox_grid'], true))
                            <span class="fit-line">{{ $q->question_text }}</span>
                        @else
                            {{ $q->question_text }}
                        @endif
                    </td>
                    <td>
                        <span class="badge type-{{ $q->gform_item_type }} {{ in_array($q->gform_item_type, ['multiple_choice_grid', 'checkbox_grid'], true) ? 'badge-small' : '' }}" 
                              style="display: inline-block; width: 100%; min-width: 220px; max-width: 240px; padding: 10px 14px; border: 1.5px solid var(--ink); border-radius: 12px; font-weight: 700; text-align: left; font-size: 13px; box-sizing: border-box; whitespace: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $typeLabels[$q->gform_item_type] ?? \Illuminate\Support\Str::headline($q->gform_item_type) }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <h3>Rubrik Penilaian</h3>
    <p class="muted">Rubrik boleh dikosongkan; AI akan menilai sendiri berdasarkan isi jawaban. Pertanyaan yang ditandai "Tidak dinilai" hanya dicatat.</p>

    @forelse($paragraphs as $q)
        <div class="card" style="margin-bottom:16px">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap">
                <div style="flex:1 1 320px">
                    <b>{{ $q->question_text }}</b>
                    @if($q->description)<div class="muted">{{ $q->description }}</div>@endif
                </div>
                <div>
                    <label for="mode-{{ $q->id }}" class="muted" style="margin:0 0 4px">Mode penilaian</label>
                    <select id="mode-{{ $q->id }}" name="modes[{{ $q->id }}]" form="exam-form" data-mode-toggle="rubric-{{ $q->id }}" data-qid="{{ $q->id }}" data-graded="{{ $graded[$q->id] ?? 0 }}">
                        <option value="scoring" @selected($q->grading_mode === 'scoring')>Dinilai</option>
                        <option value="non_scoring" @selected($q->grading_mode !== 'scoring')>Tidak dinilai</option>
                    </select>
                </div>
            </div>

            <div class="field" id="rubric-{{ $q->id }}" style="margin-top:14px" @if($q->grading_mode !== 'scoring') hidden @endif>
                <label for="key-{{ $q->id }}">Rubrik penilaian (opsional)</label>
                <textarea id="key-{{ $q->id }}" name="keys[{{ $q->id }}]" form="exam-form" style="min-height:110px" placeholder="Kosongkan agar AI menilai sendiri.">{{ $q->answer_key }}</textarea>
            </div>

            <form method="POST" action="{{ route('questions.regrade', $q) }}" class="row-actions" data-ajax data-regrade-for="{{ $q->id }}" @if($q->grading_mode !== 'scoring') hidden @endif data-confirm="Semua jawaban pertanyaan ini akan dinilai ulang dan nilai lama diganti. Lanjutkan?">
                @csrf
                <button type="submit">Nilai ulang semua jawaban pertanyaan ini</button>
            </form>
        </div>
    @empty
        <div class="card muted">Belum ada pertanyaan bertipe Paragraf pada formulir ini, jadi belum ada rubrik yang bisa diatur.</div>
    @endforelse
@endif

<dialog id="unsaved-dialog" class="dlg" aria-labelledby="dlg-title" aria-describedby="dlg-desc">
    <div class="dlg-box">
        <div class="dlg-head">
            <span class="dlg-icon" aria-hidden="true">!</span>
            <div>
                <h3 id="dlg-title">Simpan perubahan sebelum melanjutkan?</h3>
                <p id="dlg-desc" class="muted"><b id="dirty-count">0</b> perubahan pada susunan formulir atau rubrik belum disimpan.</p>
            </div>
            <button type="button" class="dlg-x" id="dlg-cancel-x" data-close aria-label="Tutup">&times;</button>
        </div>
        <div class="dlg-options">
            <button type="button" id="dlg-save" class="dlg-opt dlg-opt-primary" autofocus>
                <b>Simpan dan Lanjutkan</b>
                <span>Perubahan disimpan, lalu penilaian langsung diaktifkan.</span>
            </button>
            <button type="button" id="dlg-continue" class="dlg-opt dlg-opt-danger">
                <b>Lanjutkan tanpa Menyimpan</b>
                <span>Perubahan dibuang. Penilaian diaktifkan dengan pengaturan yang tersimpan terakhir.</span>
            </button>
            <button type="button" id="dlg-cancel" class="dlg-opt" data-close>
                <b>Batal</b>
                <span>Kembali ke halaman ini untuk melanjutkan mengedit.</span>
            </button>
        </div>
    </div>
</dialog>

<dialog id="delete-dialog" class="dlg" aria-labelledby="del-title" aria-describedby="del-desc">
    <div class="dlg-box">
        <div class="dlg-head">
            <span class="dlg-icon dlg-icon-danger" aria-hidden="true">!</span>
            <div>
                <h3 id="del-title">Hapus penilaian ini?</h3>
                <p id="del-desc" class="muted">Tindakan ini tidak bisa dibatalkan.</p>
            </div>
            <button type="button" class="dlg-x" data-close aria-label="Tutup">&times;</button>
        </div>
        <div class="dlg-warn">
            <p>Data berikut akan <b>dihapus permanen dari database</b>:</p>
            <ul>
                <li>Pengaturan dan rubrik penilaian</li>
                <li><b>{{ $exam->submissions()->count() }}</b> pengiriman responden beserta jawabannya</li>
                <li>Semua nilai dan catatan koreksi</li>
            </ul>
            <p class="muted" style="margin:0">Unduh nilai ke Excel dulu jika masih diperlukan. Jawaban asli di Google Form Anda tidak ikut terhapus.</p>
        </div>
        <div class="dlg-actions">
            <button type="button" class="btn-outline" data-close autofocus>Batal</button>
            <button type="button" id="del-confirm" class="danger">Lanjutkan Hapus</button>
        </div>
    </div>
</dialog>

<dialog id="blocked-dialog" class="dlg dlg-center" aria-labelledby="blk-title" aria-describedby="blocked-text">
    <div class="dlg-box">
        <span class="dlg-icon dlg-icon-lg" aria-hidden="true">!</span>
        <h3 id="blk-title">Penilaian belum bisa diaktifkan</h3>
        <p id="blocked-text" class="muted"></p>
        <button type="button" class="btn-block" data-close autofocus>Mengerti</button>
    </div>
</dialog>

@endsection