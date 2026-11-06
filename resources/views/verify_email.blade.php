@extends('layout')
@section('title', 'Aktifkan akun')

@section('content')
<div class="card" style="max-width:520px; margin:24px auto">
    <h1 style="font-size:30px">Cek email Anda</h1>
    <p>Kami sudah mengirim tautan aktivasi ke <b>{{ auth()->user()->email }}</b>. Buka email itu dan klik tombol <b>Aktifkan akun</b> untuk mulai memakai aplikasi.</p>
    <p class="muted">Tautan berlaku 60 menit. Belum menerima email? Periksa folder spam atau kirim ulang.</p>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">Kirim ulang email</button>
    </form>
    <p class="auth-foot muted">
        Salah memasukkan email? <a href="{{ route('account.show') }}">Hapus akun ini</a> lalu daftar ulang.
    </p>
</div>
@endsection