@extends('layout')
@section('title', 'Daftar')

@section('content')
@include('shape-grid')
@include('auth-glass')
@include('auth-mascots')
@php $mode = config('registration.mode', 'open'); @endphp
<div class="auth" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="card auth-card" style="width: 100%; max-width: 480px;">
        <div class="auth-head">
            <h1>Buat akun Anda</h1>
            <p class="muted">Selamat datang! Isi data berikut untuk mulai.</p>
        </div>

        @if($errors->any())
            <div class="auth-errors" role="alert">
                @foreach($errors->all() as $e)<div class="error">{{ $e }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            {{-- Tombol Google memakai formulir yang sama agar centang persetujuan ikut diperiksa server --}}
            <button type="submit" class="btn btn-outline btn-block btn-google"
                    formaction="{{ route('auth.google') }}" formnovalidate name="intent" value="register">
                @include('google_icon')
                Google
            </button>

            <p class="muted" style="margin:10px 0 0; font-size:11.5px; text-align:center">
                Dengan melanjutkan dengan Google, Anda menyetujui
                <a href="{{ route('terms') }}" target="_blank" rel="noopener">Ketentuan Layanan</a> dan
                <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Kebijakan Privasi</a>.
            </p>

            <div class="divider"><span>atau</span></div>

            <div class="name-row">
                <div class="field">
                    <label for="first_name">Nama depan</label>
                    <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Nama depan" autocomplete="given-name" required>
                </div>
                <div class="field">
                    <label for="last_name">Nama belakang(opsional)</label>
                    <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Nama belakang" autocomplete="family-name">
                </div>
            </div>

            <div class="field">
                <label for="email">Alamat email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan alamat email Anda" autocomplete="email" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="pw">
                    <!-- Menambahkan atribut data-strength -->
                    <input id="password" type="password" name="password" placeholder="Buat password (minimal 10 karakter)" autocomplete="new-password" minlength="10" data-strength required>
                    <button type="button" class="pw-toggle" data-target="password" aria-label="Tampilkan password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            @if($mode === 'invite')
                <div class="field">
                    <label for="invite_code">Kode undangan</label>
                    <input id="invite_code" type="password" name="invite_code" autocomplete="off" placeholder="Kode dari pengelola" required>
                </div>
            @endif

            <div class="consent">
                <input id="agree" type="checkbox" name="agree" value="1" @checked(old('agree')) required>
                <label for="agree">
                    Saya menyetujui
                    @if(\Illuminate\Support\Facades\Route::has('terms'))
                        <a href="{{ route('terms') }}" target="_blank" rel="noopener">Ketentuan Layanan</a>
                    @else
                        Ketentuan Layanan
                    @endif
                    dan <a href={{ route('privacy') }} target="_blank" rel="noopener">Kebijakan Privasi</a>
                </label>
            </div>

            <button type="submit" class="btn-block">Lanjutkan</button>
        </form>

        <p class="auth-foot muted">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </div>
</div>
@endsection