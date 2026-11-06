@extends('layout')
@section('title', 'Kebijakan Privasi')
@section('content')
<div class="card" style="max-width:760px; margin:24px auto">
    <h1 style="margin-top:0">Kebijakan Privasi</h1>
    <p class="muted">Terakhir diperbarui: 23 Oktober 2026</p>

    <h3>Pengelola</h3>
    <p>Layanan Penilai Esai Otomatis dikelola oleh Fadhlan Ilhami. Kontak: Ig: fadhilhmi.</p>

    <h3>Data yang kami proses</h3>
    <ul>
        <li><b>Akun pembuat:</b> nama, alamat email, dan kata sandi (disimpan dalam bentuk hash).</li>
        <li><b>Koneksi Google:</b> alamat email akun Google dan token akses yang disimpan terenkripsi.
            Izin yang diminta hanya baca: membaca struktur dan jawaban Google Form yang pembuat daftarkan.</li>
        <li><b>Masuk Lanjutkan dengan Google:</b> nama dan alamat email dari akun Google, hanya untuk membuat akun dan masuk.</li>
        <li><b>Data responden:</b> nama, ID, grup, email (bila Form mengumpulkannya), dan teks jawaban esai, sebagaimana diisi di Google Form pembuat.</li>
    </ul>

    <h3>Tujuan penggunaan</h3>
    <p>
        Data dipakai hanya untuk menampilkan dan menilai jawaban esai bagi pembuat pemilik penilaian.
        Data tidak dijual dan tidak dipakai untuk iklan.
    </p>

    <h3>Pihak ketiga</h3>
    <ul>
        <li><b>Penyedia AI:</b> teks pertanyaan, rubrik, dan teks jawaban responden dikirim ke penyedia layanan AI untuk dinilai.
            Nama dan ID responden tidak ikut dikirim.</li>
        <li><b>Penyedia hosting:</b> data disimpan pada server penyedia hosting yang kami gunakan.</li>
    </ul>

    <h3>Data pengguna Google</h3>
    <p>
        Penggunaan dan pengalihan informasi yang diterima dari Google API oleh aplikasi ini mengikuti
        Google API Services User Data Policy, termasuk persyaratan Limited Use.
        Akses dapat dicabut di myaccount.google.com/permissions.
    </p>

    <h3>Penyimpanan dan penghapusan</h3>
    <p>
        Data penilaian disimpan sampai pembuat menghapus penilaian tersebut. Menghapus penilaian ikut menghapus seluruh jawaban
        dan nilai di dalamnya. Pembuat dapat menghapus akunnya sendiri dari menu Akun, dan semua data miliknya ikut terhapus.
        Pengelola dapat menetapkan batas waktu penyimpanan: penilaian yang tidak aktif dan tanpa aktivitas melewati batas itu
        dihapus otomatis. Permintaan penghapusan lain dapat dikirim ke kontak di atas.
    </p>

    <h3>Keamanan</h3>
    <p>
        Akses memakai HTTPS, token Google dan API key disimpan terenkripsi, dan setiap pembuat hanya dapat melihat
        penilaian miliknya sendiri.
    </p>

    <h3>Perubahan</h3>
    <p>Kebijakan ini dapat diperbarui. Tanggal pembaruan terakhir tercantum di bagian atas halaman.</p>
</div>
@endsection