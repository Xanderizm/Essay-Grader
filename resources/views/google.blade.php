@extends('layout')
@section('title', 'Hubungkan Google')

@push('styles')
<style>
    .google-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: stretch; }
    .google-grid .card { max-width: none !important; margin-bottom: 0 !important; display: flex; flex-direction: column; justify-content: space-between; height: 100%; }
    @media (max-width: 820px) {
        .google-grid { grid-template-columns: 1fr; }
        .google-grid .card > div,
        .google-grid .card > form { min-width: 0; }
        .google-grid .row-actions { flex-direction: column; align-items: stretch; }
        .google-grid .row-actions .btn,
        .google-grid .card .btn,
        .google-grid .card button[type="submit"] {
            width: 100%;
            text-align: center;
            min-height: 44px;
        }
    }
</style>
@endpush

@section('content')
<p><a href="{{ route('dashboard') }}">&larr; Kembali</a></p>
<h2>Hubungkan Akun Google</h2>
<p class="muted">
    Dengan izin ini, aplikasi dapat membaca pertanyaan dan jawaban di Google Form Anda.
    Izinnya hanya untuk membaca. Aplikasi tidak bisa mengubah atau menghapus Form Anda.
</p>

<div class="google-grid">
    <div class="card">
        <div>
            @if($user->google_refresh_token)
                <p><span class="badge ok">Terhubung</span> sebagai <b>{{ $user->google_email }}</b></p>
                <p class="muted">
                    Sejak {{ \Illuminate\Support\Carbon::parse($user->google_connected_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}.
                    Jika koneksi berakhir, tombol Hubungkan Google akan muncul kembali.
                </p>
            @else
                <p><span class="badge bad">Belum terhubung</span></p>
                <p class="muted">Jika Google menampilkan peringatan "belum memverifikasi aplikasi ini", klik <b>Lanjutan</b>, lalu <b>Buka Penilai Esai</b>. Aplikasi hanya meminta izin membaca Google Form Anda.</p>
            @endif
        </div>

        <!-- Bagian Tengah (Poin Keterangan Izin Akses) -->
        <div style="margin: 15px 0; padding: 10px 0; border-top: 1px solid rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.1);">
            <p class="muted" style="font-size: 0.85rem; margin-bottom: 5px;">✓ Membaca struktur pertanyaan & jawaban Google Form</p>
            <p class="muted" style="font-size: 0.85rem; margin: 0;">✓ Bersifat read-only, tidak dapat mengubah/menghapus data</p>
        </div>

        <!-- Bagian Paling Bawah (Tombol Aksi) -->
        <div>
            @if($user->google_refresh_token)
                <div class="row-actions" style="margin-top: 0;">
                    <a class="btn" href="{{ route('google.connect') }}">Hubungkan Google</a>
                </div>
            @else
                <a class="btn" href="{{ route('google.connect') }}">Hubungkan Google</a>
            @endif
        </div>
    </div>

    @if($user->google_refresh_token)
        <div class="card">
            <div>
                <h3 style="margin-top:0">Coba baca Google Form</h3>
                <p class="muted">
                    Tempel link Form dari mode edit (alamatnya mengandung <code>/edit</code>).
                    Tes ini hanya membaca, tidak menyimpan apa pun.
                </p>
            </div>
            <form method="POST" action="{{ route('google.test') }}">
                @csrf
                <input type="text" name="form_url" value="{{ old('form_url') }}"
                       placeholder="https://docs.google.com/forms/d/.../edit" required>
                <br><br>
                <button type="submit">Coba Baca Form</button>
            </form>
        </div>
    @endif
</div>
@endsection