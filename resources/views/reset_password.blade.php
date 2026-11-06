@extends('layout')
@section('title', 'Password baru')

@section('content')
<div class="card" style="max-width:460px; margin:24px auto">
    <h1 style="font-size:30px">Buat password baru</h1>
    @if($errors->any())
        <div role="alert" style="margin-bottom:14px">@foreach($errors->all() as $e)<div class="error">{{ $e }}</div>@endforeach</div>
    @endif
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ old('email', $email) }}">
        <div class="field">
            <label for="password">Password baru (minimal 10 karakter)</label>
            <div class="pw">
                <!-- Menambahkan atribut data-strength -->
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="10" data-strength required autofocus>
                <button type="button" class="pw-toggle" data-target="password" aria-label="Tampilkan password" aria-pressed="false">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
        <div class="field">
            <label for="password_confirmation">Ulangi password baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn-block">Simpan password</button>
    </form>
</div>
@endsection