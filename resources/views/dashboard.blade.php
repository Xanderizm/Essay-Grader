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
    @media (max-width: 820px) {
        .exam-grid, .empty { grid-template-columns: 1fr; }
        .empty-laptop { max-width: 180px; margin: 0 auto; }
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
            @php $dark = in_array($i % 4, [1, 2], true); @endphp
            <article class="card exam-card {{ $dark ? 'card-dark' : '' }}">
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
        @endforeach
    </div>
@endif
@endsection
