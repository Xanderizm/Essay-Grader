@extends('layout')
@section('title', 'Lupa password')

@section('content')
<div class="card" style="max-width:460px; margin:24px auto">
    <h1 style="font-size:30px">Lupa password?</h1>
    <p class="muted">Masukkan email akun Anda. Kami kirim tautan untuk membuat password baru.</p>
    @error('email')<p class="error" role="alert">{{ $message }}</p>@enderror
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <label for="email">Alamat email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>
        <button type="submit" class="btn-block">Kirim tautan</button>
    </form>
    <p class="auth-foot muted"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
</div>
@endsection