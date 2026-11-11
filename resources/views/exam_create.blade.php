@extends('layout')
@section('title', 'Buat Penilaian')

@push('styles')
<style>
    .create-grid { display: grid; grid-template-columns: 640px minmax(0, 1fr); gap: 20px; align-items: stretch; }
    .create-grid > .card { margin: 0; display: flex; flex-direction: column; justify-content: space-between; }
    
    .guide { background: var(--lime); color: var(--dark-text); }
    .guide h3 { margin: 0 0 12px; color: var(--dark-text); }
    .guide p, .guide li { color: var(--dark-text); font-size: 12.5px; }
    .guide ol { margin: 0 0 16px; padding-left: 20px; display: grid; gap: 8px; }
    .guide code { background: rgba(255, 255, 255, .6); color: var(--dark-text); }
    .guide .guide-note { margin: 0; padding-top: 14px; border-top: 1.5px solid var(--dark-text); }
    .guide .guide-types { display: flex; gap: 8px; flex-wrap: wrap; margin: 8px 0 0; }
    .guide .guide-types span { padding: 1px 10px; border: 1.5px solid var(--dark-text); border-radius: 999px; background: rgba(255, 255, 255, .6); font: 700 11px/1.7 var(--mono); }
    @media (max-width: 900px) {
        .create-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<p><a href="{{ route('dashboard') }}">&larr; Kembali</a></p>

<div class="create-grid">
    <div class="card">
        <h2 style="margin-top:0">Buat Penilaian Baru</h2>

        @if(! $connected)
            <p>Akun Google Anda belum terhubung.</p>
            <a class="btn" href="{{ route('google.index') }}">Hubungkan Google</a>
        @else
            <ul class="muted" style="font-size: 13px; line-height: 1.6; margin: 0 0 24px; padding-left: 20px; display: grid; gap: 6px;">
                <li><strong>Struktur Soal:</strong> Mengambil soal essay & kunci jawaban secara instant.</li>
                <li><strong>Ekstraksi Jawaban:</strong> Mengidentifikasi data & respon siswa yang sudah masuk.</li>
                <li><strong>Koneksi AI:</strong> Menyiapkan penilai otomatis berbasis rubrik ujian Anda.</li>
            </ul>
                <form method="POST" action="{{ route('exams.store') }}" class="form-container">
                    @csrf
                    <div style="margin-bottom: 16px;">
                        <label for="form_url" style="display: block; margin-bottom: 8px;">Link Google Form</label>
                        <input type="text" id="form_url" name="form_url" value="{{ old('form_url') }}"
                            placeholder="https://docs.google.com/forms/d/.../edit" required autofocus style="width: 100%;">
                        @error('form_url')<p class="error">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit">Analisis Form</button>
                </form>
        @endif
    </div>

    <aside class="card guide" aria-label="Panduan cepat">
        <h3>Panduan cepat</h3>
        <p><b>Cara menyalin link mode edit:</b></p>
        <ol>
            <li>Buka Google Form Anda lewat akun Google yang terhubung.</li>
            <li>Pastikan Anda berada di halaman penyusun Form (bukan halaman responden).</li>
            <li>Salin alamat dari address bar. Akhirannya harus <code>/edit</code>, contoh <code>docs.google.com/forms/d/.../edit</code>.</li>
            <li>Link pendek <code>forms.gle</code> dan link responden <code>/viewform</code> tidak bisa dipakai.</li>
        </ol>
    </aside>
</div>
@endsection