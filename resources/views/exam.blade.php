@extends('layout')
@section('title', 'Nilai ' . ($exam->title ?? ''))
@push('styles')
<style>
    .toolbar { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin: 12px 0; }
    .toolbar select { min-width: 220px; }
    .toolbar-search { display: flex; gap: 8px; align-items: center; flex: 1 1 280px; justify-content: flex-end; }
    .toolbar-search input { max-width: 340px; }
    
    /* Header tabel rata kiri secara horizontal, tetapi rata tengah pada sumbu Y (vertical) */
    th { text-align: left; vertical-align: middle; padding: 10px 12px; }

    th select { min-width: 140px; font-weight: 700; text-align: left; }
    
    /* Baris Tindakan Atas: Tombol Aksi di Kiri & Dropdown Grafik Sejajar di Kanan */
    .top-actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 10px; }
    .top-actions .btn-group { display: flex; gap: 8px; align-items: center; }
    .chart-bar { display: flex; align-items: center; gap: 8px; margin-bottom: 0; }
    .chart-bar select { min-width: 180px; }

    /* Hasil & Ringkasan Kotak Grafik */
    .results-top { display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, 380px); gap: 14px; align-items: stretch; margin-bottom: 12px; }
    .chart-card { display: flex; flex-direction: column; padding: 10px 12px; min-height: 160px; }
    .chart-host { flex: 1; min-height: 0; display: flex; flex-direction: column; }
    .chart-host .plot { position: relative; flex: 1; min-height: 110px; }
    .chart-host .plot svg { position: absolute; inset: 0; width: 100%; height: 100%; }
    .chart-host p { margin: 6px 0 0; font-size: 11px; }
    @media (max-width: 820px) { .results-top { grid-template-columns: 1fr; } .chart-host .plot { min-height: 180px; } }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 820px) { .two-col { grid-template-columns: 1fr; } }

    /* Tata letak ringkasan & posisi angka di tengah */
    .summary-card-content {
        display: flex;
        flex-direction: column;
        gap: 10px;
        justify-content: space-between;
    }
    .summary-stats-grid {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        justify-content: space-between;
    }
    .summary-stats-grid > div {
        text-align: center;
        flex: 1;
        min-width: 75px;
    }
    .summary-footer-info {
        font-size: 12px;
        color: var(--muted);
        border-top: 1px solid #e3e3df;
        padding-top: 8px;
    }

    /* Kontainer sejajar untuk urutkan dan badge jumlah responden */
    .sort-badge-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    /* Kotak badge hijau lime dengan tinggi menyamakan select dropdown */
    .respondent-badge {
        background-color: #c4f55d;
        border: 1.5px solid #1b1b1b;
        border-radius: 10px;
        padding: 0 12px;
        font-weight: 700;
        font-size: 13px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        box-shadow: 0 2px 0 #1b1b1b;
    }

    /* Efisiensi & Responsivitas Kolom Pertanyaan Tabel */
    /* Border dibuat 'separate' (tampilan sama, hanya garis bawah sel) supaya garis ikut bergeser bersama header yang menempel */
    #results-table {
        --th-top: 104px;              /* jarak header dari tepi atas layar saat menempel: sesuaikan dengan tinggi topbar */
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    #results-table th { background: var(--mist); }
    #results-table.is-stuck th {
        position: relative; z-index: 6; border-radius: 0; will-change: transform;
        box-shadow: 0 2px 0 var(--line-2), 0 10px 14px -10px rgba(0, 0, 0, .35);
    }
    @media (max-width: 820px) { #results-table { --th-top: 84px; } }
    .question-cell {
        min-width: 200px;
        width: auto;
        white-space: normal;
        word-break: break-word;
    }

    @media (max-width: 768px) {
        .top-actions { flex-direction: column; align-items: stretch; gap: 10px; }
        .top-actions .btn-group { flex-direction: column; width: 100%; }
        .top-actions .btn-group .btn { width: 100%; min-height: 44px; text-align: center; }
        .chart-bar { width: 100%; }
        .chart-bar select { width: 100%; min-height: 44px; }
        .toolbar { flex-direction: column; align-items: stretch; gap: 12px; }
        .toolbar > div { width: 100%; }
        .sort-badge-container { width: 100%; }
        .sort-badge-container select { flex: 1; min-height: 44px; }
        .toolbar-search { width: 100%; max-width: 100%; }
        .toolbar-search input { flex: 1; min-height: 44px; max-width: none; }
        .toolbar-search button, .toolbar-search .btn { min-height: 44px; }
        .summary-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; }
        #results-table { min-width: 620px; }
    }
</style>
@endpush
@section('content')
@include('shape-grid', ['scope' => 'box'])
@php
    $badge   = ['Sangat Baik' => 'ok', 'Baik' => 'info', 'Cukup' => 'warn', 'Kurang' => 'bad'];
    $pending = ($counts['pending'] ?? 0) + ($counts['processing'] ?? 0);
    $failed  = $counts['failed'] ?? 0;
@endphp

<p><a href="{{ route('dashboard') }}">&larr; Kembali</a></p>
<h2>{{ $exam->title }}</h2>

@if($errors->any())
    <div class="card" style="border-left:4px solid #991b1b; margin-bottom:16px">
        @foreach($errors->all() as $e)<div class="error">{{ $e }}</div>@endforeach
    </div>
@endif

@if($questions->isEmpty())
    <div class="card muted">Belum ada pertanyaan berjenis paragraf dalam mode dinilai. Atur peran kolom di halaman Kelola Penilaian.</div>
@else
    <div id="live-root" data-live-url="{{ route('exams.live', $exam) }}" data-live-page="results" data-version="{{ $live['version'] }}">
    
    {{-- BARIS TOMBOL & DROPDOWN SEBARAN NILAI SEJAJAR --}}
    <div class="top-actions">
        <div class="btn-group">
            <a class="btn" href="{{ route('exams.edit', $exam) }}">Kelola Penilaian</a>
            @if($exam->submissions()->exists())
                <a class="btn" href="{{ route('exams.export', ['exam' => $exam, 'semua' => $all ? 1 : null]) }}">Unduh Nilai (Excel)</a>
            @else
                <span class="btn btn-outline" aria-disabled="true" title="Belum ada jawaban" style="opacity:.5; pointer-events:none">Unduh Nilai (Excel)</span>
            @endif
        </div>

        <div class="chart-bar">
            <select id="chart-item">
                @foreach($labels as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
            </select>
        </div>
    </div>

    <div class="results-top">
        <div class="card summary-card-content" style="margin-bottom:0">
            <div class="summary-stats-grid">
                <div><div class="muted">Peserta</div><b style="font-size:22px">{{ $rows->count() }}</b></div>
                <div><div class="muted">Rata-rata</div><b style="font-size:22px">{{ $classAverage !== null ? number_format($classAverage, 1) : '-' }}</b></div>
                @foreach($distribution as $label => $total)
                    <div>
                        <div class="muted"><span class="badge {{ $badge[$label] ?? '' }}">{{ $label }}</span></div>
                        <b style="font-size:22px">{{ $total }}</b>
                    </div>
                @endforeach
            </div>

            <div class="summary-footer-info">
                Selesai dinilai: {{ $counts['success'] ?? 0 }} &bull; Menunggu: {{ $pending }} &bull; Gagal: {{ $failed }}
                &bull; Dikoreksi pembuat: {{ $overridden }}
            </div>

            @if($failed > 0)
                <form method="POST" action="{{ route('exams.retry', $exam) }}" style="margin-top:2px" data-ajax>
                    @csrf
                    <button type="submit">Coba nilai ulang yang gagal ({{ $failed }})</button>
                </form>
            @endif

            @if($pending > 0)
                <p class="muted" style="margin:0;">Sebagian jawaban masih dinilai. Halaman diperbarui otomatis.</p>
            @endif
        </div>
        <div class="card chart-card">
            <div id="score-chart" class="chart-host"></div>
            <script type="application/json" id="chart-data">{!! json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        </div>
    </div>

    <p class="muted" style="margin-bottom:14px">
        <b>K</b> = Konten, <b>L</b> = Logika, <b>S</b> = Struktur (skala 0-100). Status <span class="badge warn">dekat batas</span> menandakan nilai berada 3 poin dari batas kategori dan disarankan untuk diperiksa kembali. Nilai AI bersifat sebagai referensi penunjang, bukan keputusan mutlak.
    </p>

    <form method="GET" action="{{ route('exams.show', $exam) }}" class="toolbar" id="toolbar-form">
        <input type="hidden" name="kolom" value="{{ $colId }}">
        @if($all)<input type="hidden" name="semua" value="1">@endif
        <div>
            <label for="urut">Urutkan (A-Z)</label>
            <div class="sort-badge-container">
                <select id="urut" name="urut">
                    @foreach($identityFields as $f)
                        <option value="{{ $f->id }}" @selected((string) $sort === (string) $f->id)>{{ $f->question_text }}</option>
                    @endforeach
                    <option value="nilai" @selected($sort === 'nilai')>Nilai tertinggi</option>
                </select>
                <div class="respondent-badge">{{ $rows->count() }} responden</div>
            </div>
        </div>
        <div class="toolbar-search">
            <input type="search" id="cari-input" name="cari" value="{{ $search }}" aria-label="Cari responden"
                placeholder="{{ $identityFields->isNotEmpty() ? 'Cari ' . $identityFields->pluck('question_text')->implode(' / ') : 'Cari responden' }}">
            <button type="submit">Cari</button>
            @if($search !== '')
                <a class="btn btn-outline" href="{{ route('exams.show', ['exam' => $exam, 'urut' => $sort, 'kolom' => $colId, 'semua' => $all ? 1 : null]) }}">Reset</a>
            @endif
        </div>
    </form>
    {{-- Elemen row-count tersembunyi agar teks responden lama tidak tampil --}}
    <span id="row-count" hidden></span>
    @if($hidden > 0 && ! $all)
        <p class="muted">{{ $hidden }} pengiriman ganda disembunyikan (yang tampil: terbaru).
            <a href="{{ route('exams.show', ['exam' => $exam, 'urut' => $sort, 'kolom' => $colId, 'semua' => 1]) }}">Tampilkan semua</a></p>
    @elseif($all)
        <p class="muted">Menampilkan semua pengiriman.
            <a href="{{ route('exams.show', ['exam' => $exam, 'urut' => $sort, 'kolom' => $colId]) }}">Hanya yang terbaru</a></p>
    @endif

    <div class="card" style="overflow-x:auto">
        <table id="results-table">
            <tr>
                <th style="text-align: left;">
                    <select id="id-col" aria-label="Kolom identitas responden" style="text-align: left; margin: 0;">
                        @forelse($identityFields as $f)
                            <option value="{{ $f->id }}" @selected($colId === $f->id)>{{ $f->question_text }}</option>
                        @empty
                            <option>Responden</option>
                        @endforelse
                    </select>
                </th>
                @foreach($questions as $i => $q)
                    <th title="{{ $q->question_text }}">{{ $labels[$q->id] }}</th>
                @endforeach
                <th>Rata-rata</th>
                <th>Kategori</th>
            </tr>

            @forelse($rows as $row)
                @php($s = $row['submission'])
                <tr data-row
                    data-search="{{ mb_strtolower(collect($s->identity ?? [])->implode(' ')) }}"
                    data-avg="{{ $row['average'] ?? -1 }}"
                    @foreach($identityFields as $f) data-i-{{ $f->id }}="{{ mb_strtolower((string) (($s->identity ?? [])[$f->id] ?? '')) }}" @endforeach>
                    <td>
                        @foreach($identityFields as $f)
                            <span data-col="{{ $f->id }}" @if($f->id !== $colId) hidden @endif>{{ ($s->identity ?? [])[$f->id] ?? '-' }}</span>
                        @endforeach
                        @if($identityFields->isEmpty())-@endif
                    </td>

                    @foreach($questions as $q)
                        @php($a = $row['answers']->get($q->id))
                        <td class="question-cell">
                            @if(! $a)
                                <span class="muted">-</span>
                            @elseif($a->processing_status === 'success' && $a->final_score !== null)
                                <strong>{{ number_format($a->final_score, 1) }}</strong>
                                <span class="badge {{ $badge[$a->ai_category] ?? '' }}">{{ $a->ai_category }}</span>
                                @if($a->is_overridden)
                                    <span class="badge info">disesuaikan pembuat</span>
                                @endif
                                @if($near($a->final_score))
                                    <span class="badge warn">dekat batas</span>
                                @endif
                                @if($a->is_overridden && $a->ai_original)
                                <div class="muted">Nilai AI awal: {{ number_format($a->ai_original['final_score'], 1) }} ({{ $a->ai_original['category'] }})</div>
                                @endif
                                @if($a->override_note)
                                <div class="muted"><i>Catatan pembuat: {{ $a->override_note }}</i></div>
                                @endif
                                <details>
                                    <summary class="muted" style="cursor:pointer">Detail Jawaban</summary>
                                    <div class="muted"><b>Hasil:</b> K {{ $a->score_content }} &middot; L {{ $a->score_logic }} &middot; S {{ $a->score_structure }}</div>
                                    <div class="two-col" style="margin-top:6px">
                                        <div><b>Jawaban</b><br>{!! nl2br(e($a->raw_answer)) !!}</div>
                                        <div><b>Rubrik</b><br>{!! nl2br(e($q->answer_key ?: 'Tidak ada rubrik: AI menilai sendiri.')) !!}</div>
                                    </div>
                                    @if($a->ai_analysis)<div style="margin-top:6px"><b>Alasan penilaian:</b><br>{{ \Illuminate\Support\Str::limit($a->ai_analysis, 180) }}</div>@endif
                                    @include('override_form', ['a' => $a])
                                </details>
                            @elseif($a->processing_status === 'failed')
                                <span class="badge bad" title="{{ \App\Support\Friendly::error($a->last_error) }}">Gagal</span>
                                <details>
                                    <summary class="muted" style="cursor:pointer">Jawaban dan nilai manual</summary>
                                    <div style="margin-top:6px"><b>Jawaban:</b><br>{!! nl2br(e($a->raw_answer)) !!}</div>
                                    @include('override_form', ['a' => $a])
                                </details>
                            @else
                                <span class="badge warn">Menunggu</span>
                            @endif
                        </td>
                    @endforeach

                    <td>
                        @if($row['average'] !== null)
                            <strong>{{ number_format($row['average'], 1) }}</strong>
                            @unless($row['complete'])<div class="muted">sementara</div>@endunless
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($row['category'])
                            <span class="badge {{ $badge[$row['category']] ?? '' }}">{{ $row['category'] }}</span>
                            @if($row['near'])<div><span class="badge warn">dekat batas</span></div>@endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $questions->count() + 3 }}" class="muted">Belum ada jawaban masuk.</td></tr>
            @endforelse
            <tr id="no-match" hidden><td colspan="{{ $questions->count() + 3 }}" class="muted">Tidak ada responden yang cocok dengan pencarian.</td></tr>
        </table>
    </div>
    </div>
@endif

<script>
/* Header tabel nilai menempel di bawah topbar saat halaman digulir.
   CSS position:sticky tidak bisa dipakai di sini: kartu tabel memakai overflow-x:auto (agar tabel lebar bisa digeser),
   dan itu menjadikan kartu sebagai wadah gulir sehingga sticky tidak mengikuti layar. Jadi header digeser lewat transform
   tiap gulir. Elemen asli di dalam <th> (select kolom identitas, tooltip judul) tidak diubah, jadi tetap berfungsi. */
(function () {
    var table = document.getElementById('results-table');
    if (!table || !table.rows.length) { return; }
    var head = table.rows[0];
    var cells = Array.prototype.slice.call(head.cells);
    var raf = 0, last = -1;

    function update() {
        raf = 0;
        var top = parseFloat(getComputedStyle(table).getPropertyValue('--th-top')) || 104;
        var max = Math.max(0, table.offsetHeight - head.offsetHeight);
        var d = Math.round(Math.min(max, Math.max(0, top - table.getBoundingClientRect().top)));
        if (d === last) { return; }
        last = d;
        var t = d ? 'translateY(' + d + 'px)' : '';
        for (var i = 0; i < cells.length; i++) { cells[i].style.transform = t; }
        table.classList.toggle('is-stuck', d > 0);
    }
    function queue() { if (!raf) { raf = requestAnimationFrame(update); } }

    window.addEventListener('scroll', queue, { passive: true });
    window.addEventListener('resize', queue);
    if ('ResizeObserver' in window) { new ResizeObserver(queue).observe(table); }   // pencarian/urutan mengubah tinggi tabel
    update();
})();
</script>
@endsection
