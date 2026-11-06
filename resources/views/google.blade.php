@extends('layout')
@section('title', 'Hubungkan Google')
@section('content')
<p><a href="{{ route('dashboard') }}">&larr; Kembali</a></p>
<h2>Hubungkan Akun Google</h2>
<p class="muted">
    Dengan izin ini, aplikasi dapat membaca pertanyaan dan jawaban di Google Form Anda.
    Izinnya hanya untuk membaca. Aplikasi tidak bisa mengubah atau menghapus Form Anda.
</p>

<div class="card" style="margin-bottom:16px">
    @if($user->google_refresh_token)
        <p><span class="badge ok">Terhubung</span> sebagai <b>{{ $user->google_email }}</b></p>
        <p class="muted">
            Sejak {{ \Illuminate\Support\Carbon::parse($user->google_connected_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}.
            Jika koneksi berakhir, tombol Hubungkan Google akan muncul kembali.
        </p>
        <div class="row-actions">
            <a class="btn" href="{{ route('google.connect') }}">Hubungkan Google</a>
        </div>
    @else
        <p><span class="badge bad">Belum terhubung</span></p>
        <a class="btn" href="{{ route('google.connect') }}">Hubungkan Google</a>
        <p class="muted">Jika Google menampilkan peringatan "belum memverifikasi aplikasi ini", klik <b>Lanjutan</b>, lalu <b>Buka Penilai Esai</b>. Aplikasi hanya meminta izin membaca Google Form Anda.</p>
    @endif
</div>

@if($user->google_refresh_token)
    <div class="card" style="max-width:640px">
        <h3 style="margin-top:0">Coba baca Google Form</h3>
        <p class="muted">
            Tempel link Form dari mode edit (alamatnya mengandung <code>/edit</code>).
            Tes ini hanya membaca, tidak menyimpan apa pun.
        </p>
        <form method="POST" action="{{ route('google.test') }}">
            @csrf
            <input type="text" name="form_url" value="{{ old('form_url') }}"
                   placeholder="https://docs.google.com/forms/d/.../edit" required>
            <br><br>
            <button type="submit">Coba Baca Form</button>
        </form>
    </div>
@endif
@endsection