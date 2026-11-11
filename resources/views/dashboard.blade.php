@extends('layout')
@section('title', 'Penilaian saya')

@push('styles')
<style>
    .exam-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .exam-card { display: flex; flex-direction: column; justify-content: space-between; gap: 18px; min-height: 190px; }
    .exam-card h3 { margin: 0 0 10px; font-size: 19px; overflow-wrap: anywhere; }
    .exam-meta { display: flex; gap: 8px; flex-wrap: wrap; }
    .empty { display: grid; grid-template-columns: 210px 1fr; gap: 24px; align-items: center; }
    .empty-laptop {
        width: 100%;
        max-width: 210px;
        height: auto;
        display: block;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

        /* Kartu berwarna pastel: teks selalu gelap (--dark-text) di mode terang maupun gelap */
    .exam-card.card-lime  { background: var(--lime); }
    .exam-card.card-sky   { background: var(--sky); }
    .exam-card.card-amber { background: var(--amber); }
    .exam-card.card-pastel { color: var(--dark-text); border-color: var(--dark-text); box-shadow: 0 4px 0 var(--dark-text); }
    html[data-theme="dark"] .exam-card.card-pastel { box-shadow: 0 4px 0 rgba(236, 236, 232, .22); }
    .exam-card.card-pastel h3 { color: var(--dark-text); }
    .exam-card.card-pastel .badge { background: rgba(255, 255, 255, .75); color: var(--dark-text); border-color: var(--dark-text); }
    .exam-card.card-pastel .badge.bad { background: var(--coral); }
    .exam-card.card-lime  .badge.ok   { background: rgba(255, 255, 255, .75); }
    .exam-card.card-sky   .badge.ok   { background: var(--lime); }
    .exam-card.card-sky   .badge.warn { background: var(--amber); }
    .exam-card.card-amber .badge.ok   { background: var(--lime); }
    .exam-card.card-amber .badge.warn { background: rgba(255, 255, 255, .75); }
    .exam-card.card-pastel .btn { background: var(--dark-text); color: #fff; border-color: var(--dark-text); }
    .exam-card.card-pastel .btn.btn-outline { background: rgba(255, 255, 255, .75); color: var(--dark-text); }
    .exam-card.card-pastel .btn:hover { box-shadow: 3px 3px 0 var(--dark-text); }
    .exam-card.card-pastel :focus-visible { outline-color: var(--dark-text); }
    
    @media (max-width: 820px) {
        .exam-grid, .empty { grid-template-columns: 1fr; }
        .empty-laptop { max-width: 180px; margin: 0 auto; }
        .sec-head { flex-direction: column; align-items: stretch; gap: 12px; }
        .sec-head .btn { width: 100%; text-align: center; min-height: 44px; }
        .exam-card .row-actions { flex-direction: column; }
        .exam-card .row-actions .btn { width: 100%; text-align: center; min-height: 44px; }
    }
</style>
@endpush

@section('content')
@include('shape-grid', ['scope' => 'box'])
@php
    $exams->loadCount('submissions');
    $statusLabel = ['draft' => 'Draf', 'active' => 'Aktif', 'closed' => 'Ditutup'];
    $statusBadge = ['draft' => 'warn', 'active' => 'ok', 'closed' => 'bad'];
@endphp

<div class="sec-head" style="margin-top:0; justify-content:space-between; align-items:center">
    <h1 class="tag" style="font-size:26px">Penilaian saya</h1>
    <a class="btn" href="{{ route('exams.create') }}">Buat penilaian baru</a>
</div>

@if($exams->isEmpty())
    <div class="card empty">
        <img src="{{ asset('images/empty.svg') }}" class="empty-laptop" alt="Belum ada penilaian" loading="lazy">
        <div>
            <h2>Belum ada penilaian</h2>
            <p class="muted">Hubungkan akun Google, tempel link Google Form, dan sistem membaca soalnya otomatis.</p>
            <a class="btn" href="{{ route('exams.create') }}">Buat penilaian pertama</a>
        </div>
    </div>
@else
    <div class="exam-grid">
        @foreach($exams as $i => $exam)
            @php
                $tone = ['lime', 'dark', 'sky', 'amber'][$i % 4];
                $dark = $tone === 'dark';
            @endphp
            <div>
                @if($exam->gform_deleted_at)
                    <div role="alert" style="background:#FEF2F2; color:#B91C1C; border:1px solid #FECACA; border-radius:6px; padding:10px 14px; margin-bottom:8px; font-size:14px">
                        Google Form ini sudah dihapus. Penilaian dinonaktifkan dan tidak bisa diaktifkan lagi.
                    </div>
                @endif
                <article class="card exam-card {{ $dark ? 'card-dark' : 'card-pastel card-' . $tone }}">
                    <div>
                        <h3>{{ $exam->title ?? 'Menunggu analisis Form' }}</h3>
                        <div class="exam-meta">
                            @if($exam->analysis_status === 'success')
                                <span class="badge ok">Analisis berhasil</span>
                            @elseif($exam->analysis_status === 'failed')
                                <span class="badge bad">Analisis gagal</span>
                            @else
                                <span class="badge warn">Menganalisis</span>
                            @endif
                            <span class="badge {{ $statusBadge[$exam->status] ?? '' }}">{{ $statusLabel[$exam->status] ?? $exam->status }}</span>
                            <span class="badge">{{ $exam->submissions_count }} jawaban masuk</span>
                        </div>
                    </div>
                    <div class="row-actions" style="margin-top:0">
                        @if($exam->analysis_status === 'success')
                            <a class="btn" href="{{ route('exams.show', $exam) }}">Lihat nilai</a>
                        @endif
                        <a class="btn {{ $dark ? 'btn-ghost' : 'btn-outline' }}" href="{{ route('exams.edit', $exam) }}">Kelola</a>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
@endif
@endsection
