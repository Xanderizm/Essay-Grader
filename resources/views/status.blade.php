@extends('layout')
@section('title', 'Status Sistem')
@section('content')
@php
    $workerOk    = $readyCount === 0 || $lag < 120;
    $schedulerOk = $schedulerAge !== null && $schedulerAge < 180;
    $keysOk      = ($keyStats['active'] - $keyStats['cooling']) > 0;
    $backupOk    = $backupAge !== null && $backupAge < 172800;
@endphp
<p><a href="{{ route('dashboard') }}">&larr; Kembali</a></p>
<h2>Status Sistem</h2>
<p class="muted">Halaman ini memuat ulang otomatis tiap 15 detik.</p>
<script>setTimeout(function () { location.reload(); }, 15000);</script>

<div class="card" style="margin-bottom:16px; overflow-x:auto">
    <table>
        <tr><th style="width:240px">Komponen</th><th style="width:120px">Status</th><th>Keterangan</th></tr>
        <tr>
            <td>Pengambil jawaban otomatis</td>
            <td><span class="badge {{ $schedulerOk ? 'ok' : 'bad' }}">{{ $schedulerOk ? 'Hidup' : 'Mati?' }}</span></td>
            <td>
                @if($schedulerAge === null)
                    Belum pernah berjalan. Jalankan <code>php artisan schedule:work</code>.
                @else
                    Terakhir berjalan {{ $schedulerAge }} detik lalu.
                @endif
            </td>
        </tr>
        <tr>
            <td>Pemroses penilaian</td>
            <td><span class="badge {{ $workerOk ? 'ok' : 'bad' }}">{{ $workerOk ? 'Hidup' : 'Mati?' }}</span></td>
            <td>
                {{ $readyCount }} pekerjaan menunggu.
                @unless($workerOk)
                    Yang tertua menunggu {{ $lag }} detik. Jalankan <code>php artisan queue:work</code>.
                @endunless
            </td>
        </tr>
        <tr>
            <td>Kunci layanan AI</td>
            <td><span class="badge {{ $keysOk ? 'ok' : 'bad' }}">{{ $keysOk ? 'Tersedia' : 'Tidak ada' }}</span></td>
            <td>{{ $keyStats['active'] }} aktif dari {{ $keyStats['total'] }}, {{ $keyStats['cooling'] }} sedang istirahat (limit).</td>
        </tr>
        <tr>
            <td>Cadangan data</td>
            <td><span class="badge {{ $backupOk ? 'ok' : 'warn' }}">{{ $backupOk ? 'Terbaru' : 'Perlu dibuat' }}</span></td>
            <td>
                @if($backupAge === null)
                    Belum ada cadangan. Jalankan <code>php artisan db:backup</code>.
                @else
                    Terakhir {{ floor($backupAge / 3600) }} jam lalu.
                @endif
            </td>
        </tr>
        <tr>
            <td>Tugas gagal</td>
            <td><span class="badge {{ $failedJobs === 0 ? 'ok' : 'warn' }}">{{ $failedJobs }}</span></td>
            <td class="muted">Dibersihkan otomatis setelah 7 hari.</td>
        </tr>
        <tr>
            <td>Akun Google terhubung</td>
            <td><span class="badge {{ $googleUsers >= 80 ? 'warn' : 'ok' }}">{{ $googleUsers }} / 100</span></td>
            <td class="muted">Batas 100 berlaku selama aplikasi Google belum diverifikasi.</td>
        </tr>
        <tr>
            <td>Pengiriman email</td>
            <td><span class="badge {{ $mailDriver === 'log' ? 'warn' : 'ok' }}">{{ $mailDriver }}</span></td>
            <td>
                Pengirim: {{ $mailFrom }}.
                @if($mailDriver === 'log') Mode uji: email hanya ditulis ke log. @endif
                <form method="POST" action="{{ route('admin.mail-test') }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn-outline">Kirim email uji</button>
                </form>
            </td>
        </tr>
    </table>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="display:flex; gap:32px; flex-wrap:wrap">
        <div><div class="muted">Pengguna</div><b style="font-size:22px">{{ $users }}</b></div>
        <div><div class="muted">Penilaian aktif</div><b style="font-size:22px">{{ $examsByStatus['active'] ?? 0 }}</b></div>
        <div><div class="muted">Penilaian draf</div><b style="font-size:22px">{{ $examsByStatus['draft'] ?? 0 }}</b></div>
        <div><div class="muted">Penilaian ditutup</div><b style="font-size:22px">{{ $examsByStatus['closed'] ?? 0 }}</b></div>
        <div><div class="muted">Jawaban selesai</div><b style="font-size:22px">{{ $answers['success'] ?? 0 }}</b></div>
        <div><div class="muted">Menunggu</div><b style="font-size:22px">{{ ($answers['pending'] ?? 0) + ($answers['processing'] ?? 0) }}</b></div>
        <div><div class="muted">Gagal</div><b style="font-size:22px">{{ $answers['failed'] ?? 0 }}</b></div>
    </div>
</div>

@if($failedAnswers->isNotEmpty())
    <div class="card">
        <h3 style="margin-top:0">Jawaban gagal terbaru</h3>
        <table>
            <tr><th>ID</th><th>Waktu</th><th>Penyebab terakhir</th></tr>
            @foreach($failedAnswers as $f)
                <tr>
                    <td>{{ $f->id }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($f->updated_at)->timezone('Asia/Jakarta')->format('d/m H:i') }}</td>
                    <td class="muted">{{ $f->last_error }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endif
@endsection