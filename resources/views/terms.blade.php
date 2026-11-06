@extends('layout')
@section('title', 'Ketentuan Layanan')

@section('content')
<div class="card" style="max-width:760px; margin:8px auto 0">
    <h1 style="font-size:32px">Ketentuan Layanan</h1>
    <p class="muted">Terakhir diperbarui: 24 Oktober 2026. Ini templat, bukan nasihat hukum. Isi bagian bertanda [...] dan tinjau bersama pihak organisasi sebelum dipublikasikan.</p>

    <h3>Layanan</h3>
    <p>Penilai Esai Otomatis membantu pembuat menilai jawaban esai Google Form dengan bantuan AI. Layanan dikelola oleh [NAMA PENGELOLA]. Kontak: [EMAIL KONTAK].</p>

    <h3>Akun</h3>
    <p>Anda bertanggung jawab menjaga kerahasiaan password atau akses akun Google yang dipakai untuk masuk, dan atas semua aktivitas di akun Anda. Berikan informasi pendaftaran yang benar.</p>

    <h3>Tanggung jawab pembuat</h3>
    <ul>
        <li>Anda hanya mendaftarkan Google Form milik Anda atau yang Anda berhak kelola.</li>
        <li>Anda memberi tahu responden bahwa jawaban mereka diproses dengan bantuan AI, sesuai aturan institusi Anda.</li>
        <li>Anda tidak memakai layanan untuk tujuan yang melanggar hukum atau merugikan pihak lain.</li>
    </ul>

    <h3>Penilaian oleh AI</h3>
    <p>Nilai dan analisis dari AI adalah bantuan penilaian dan bisa keliru. Keputusan akhir atas nilai responden sepenuhnya ada pada pembuat. Fitur koreksi disediakan untuk itu.</p>

    <h3>Ketersediaan</h3>
    <p>Layanan disediakan apa adanya. Layanan dapat terhenti, dibatasi kuotanya, atau berubah sewaktu-waktu, termasuk karena batas pemakaian layanan pihak ketiga seperti Google dan penyedia AI. Simpan salinan nilai penting dengan mengunduhnya ke Excel.</p>

    <h3>Penghentian</h3>
    <p>Anda dapat menghapus akun kapan saja dari menu Akun. Pengelola dapat menonaktifkan akun yang melanggar ketentuan ini.</p>

    <h3>Data pribadi</h3>
    <p>Pemrosesan data diatur dalam <a href="{{ route('privacy') }}">Kebijakan Privasi</a>.</p>

    <h3>Perubahan</h3>
    <p>Ketentuan ini dapat diperbarui. Tanggal pembaruan terakhir tercantum di bagian atas halaman.</p>
</div>
@endsection
