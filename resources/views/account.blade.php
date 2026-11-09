@extends('layout')
@section('title', 'Akun')

@section('content')
<div class="sec-head" style="margin-top:0">
    <h1 class="tag" style="font-size:26px">Akun saya</h1>
</div>

@if($errors->any())
    <div class="card" style="border-left:4px solid #9b1c1c; margin-bottom:16px; max-width:560px" role="alert">
        @foreach($errors->all() as $e)<div class="error">{{ $e }}</div>@endforeach
    </div>
@endif

<div class="card" style="margin-bottom:16px; max-width:560px">
    <p style="margin-top:0"><b>{{ $user->name }}</b><br>{{ $user->email }}</p>
    <p class="muted" style="margin-bottom:0">
        Penilaian: {{ $examCount }} &middot;
        Masuk dengan: {{ $user->password_set ? 'password' : 'Google' }} &middot;
        Google Form: @if($user->google_refresh_token) terhubung ({{ $user->google_email }}) @else belum terhubung @endif
        &middot; <a href="{{ route('google.index') }}">Kelola koneksi Google</a>
    </p>
</div>

<div class="card" style="margin-bottom:16px; max-width:560px">
    <h3 style="margin-top:0">{{ $user->password_set ? 'Ganti password' : 'Buat password' }}</h3>
    @unless($user->password_set)
        <p class="muted">Akun Anda masuk lewat Google. Buat password jika ingin juga bisa masuk dengan email dan password.</p>
    @endunless
    <form method="POST" action="{{ route('account.password') }}">
        @csrf
        @method('PUT')
        @if($user->password_set)
            <div class="field">
                <label for="current_password">Password lama</label>
                <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
            </div>
        @endif
        <div class="field">
            <label for="new_password">Password baru (minimal 10 karakter)</label>
            <!-- Menambahkan atribut data-strength pada password baru -->
            <input id="new_password" type="password" name="password" autocomplete="new-password" minlength="10" data-strength required>
        </div>
        <div class="field">
            <label for="new_password_confirmation">Ulangi password baru</label>
            <input id="new_password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit">Simpan password</button>
    </form>
</div>

<div class="card" style="max-width:560px; border-left:4px solid #9b1c1c">
    <h3 style="margin-top:0">Hapus akun</h3>
    <p class="muted">Akun dijadwalkan dihapus dalam 14 hari. Selama masa itu, masuk kembali akan membatalkannya. Setelah 14 hari, semua penilaian, jawaban, dan nilai dihapus permanen dan izin Google dicabut.</p>
    <form method="POST" action="{{ route('account.destroy') }}"
          data-confirm="Hapus akun dan SEMUA data Anda secara permanen?">
        @csrf
        @method('DELETE')
        @if($user->password_set)
            <div class="field">
                <label for="del_password">Masukkan password untuk konfirmasi</label>
                <input id="del_password" type="password" name="password" autocomplete="current-password" required>
            </div>
        @else
            <div class="field">
                <label for="confirm_email">Ketik email akun Anda untuk konfirmasi</label>
                <input id="confirm_email" type="email" name="confirm_email" placeholder="{{ $user->email }}" autocomplete="off" required>
            </div>
        @endif
        <button type="submit" class="danger">Hapus akun saya</button>
    </form>
</div>
@endsection