@extends('layout')
@section('title', 'Buat Penilaian')
@section('content')
<p><a href="{{ route('dashboard') }}">&larr; Kembali</a></p>
<div class="card" style="max-width:640px">
    <h2 style="margin-top:0">Buat Penilaian Baru</h2>

    @if(! $connected)
        <p>Akun Google Anda belum terhubung.</p>
        <a class="btn" href="{{ route('google.index') }}">Hubungkan Google</a>
    @else
        <p class="muted">
            Tempel link Google Form dari mode edit (alamatnya mengandung <code>/edit</code>).
            Form harus milik akun Google yang terhubung. Struktur Form dibaca dan dianalisis otomatis.
        </p>
        <form method="POST" action="{{ route('exams.store') }}">
            @csrf
            <label>Link Google Form</label>
            <input type="text" name="form_url" value="{{ old('form_url') }}"
                   placeholder="https://docs.google.com/forms/d/.../edit" required autofocus>
            @error('form_url')<p class="error">{{ $message }}</p>@enderror
            <br><br>
            <button type="submit">Analisis Form</button>
        </form>
    @endif
</div>
@endsection