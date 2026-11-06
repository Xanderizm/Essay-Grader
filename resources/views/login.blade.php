@extends('layout')
@section('title', 'Masuk')

@section('content')
@include('shape-grid')
@include('auth-glass')
@include('auth-mascots')
@php
    $mode = config('registration.mode', 'open');
    $signupOpen = $mode === 'open' || ($mode === 'invite' && filled(config('registration.code')));
@endphp
<div class="auth" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="card auth-card" style="width: 100%; max-width: 440px;">
        <div class="auth-head">
            <h1>Masuk</h1>
            <p class="muted">Selamat datang kembali! Masuk untuk melanjutkan.</p>
        </div>

        @if($errors->has('google'))
            <div class="auth-errors" role="alert"><div class="error">{{ $errors->first('google') }}</div></div>
        @endif

        <form method="POST" action="{{ route('auth.google') }}">
            @csrf
            <input type="hidden" name="intent" value="login">
            <button type="submit" class="btn btn-outline btn-block btn-google">
                @include('google_icon')
                Lanjutkan dengan Google
            </button>
        </form>

        <p class="muted" style="margin:10px 0 0; font-size:11.5px; text-align:center">
            Dengan melanjutkan dengan Google, Anda menyetujui
            <a href="{{ route('terms') }}" target="_blank" rel="noopener">Ketentuan Layanan</a> dan
            <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Kebijakan Privasi</a>.
        </p>

        <div class="divider"><span>atau</span></div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label for="email">Alamat email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan alamat email Anda" autocomplete="email" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="pw">
                    <input id="password" type="password" name="password" placeholder="Masukkan password Anda" autocomplete="current-password" required>
                    <button type="button" class="pw-toggle" data-target="password" aria-label="Tampilkan password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                @error('email')<p class="error" role="alert" style="margin:8px 0 0">{{ $message }}</p>@enderror
                <p class="muted" style="margin:8px 0 0"><a href="{{ route('password.request') }}">Lupa password?</a></p>
            </div>
            <button type="submit" class="btn-block">Masuk</button>
        </form>

        @if($signupOpen)
            <p class="auth-foot muted">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
        @endif
    </div>
</div>
@endsection 