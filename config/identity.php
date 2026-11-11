<?php

// Daftar kata untuk mengenali kolom identitas responden (umum: pelajar, mahasiswa, karyawan, masyarakat).
// Tambahkan kata baru di sini jika ada kolom identitas yang belum terdeteksi. Gunakan huruf kecil, satu kata per item.
return [

    // Kata yang menandakan data diri. Judul harus memuat minimal satu kata ini.
    'words' => [
        // nomor identitas dan akun
        'nama', 'nim', 'npm', 'nis', 'nisn', 'nip', 'nidn', 'nik', 'nuptk', 'npwp', 'ktp', 'sim', 'paspor', 'passport',
        'induk', 'email', 'mail', 'surel', 'username', 'akun', 'id',
        // pendidikan
        'kelas', 'semester', 'tingkat', 'jenjang', 'jurusan', 'prodi', 'studi', 'fakultas', 'angkatan', 'rombel',
        'peminatan', 'absen', 'murid', 'pelajar', 'wali', 'sekolah', 'kampus', 'universitas', 'institut', 'pendidikan',
        // pekerjaan dan organisasi
        'instansi', 'lembaga', 'organisasi', 'komunitas', 'perusahaan', 'kantor', 'karyawan', 'pegawai', 'jabatan',
        'posisi', 'divisi', 'departemen', 'unit', 'profesi', 'pekerjaan',
        // alamat
        'alamat', 'domisili', 'kota', 'kabupaten', 'kecamatan', 'kelurahan', 'desa', 'provinsi', 'negara',
        'kewarganegaraan', 'pos', 'rt', 'rw',
        // kontak
        'telepon', 'telp', 'tlp', 'hp', 'whatsapp', 'wa', 'telegram', 'instagram', 'ig', 'kontak',
        // data pribadi
        'usia', 'umur', 'lahir', 'ttl', 'kelamin', 'gender', 'agama', 'status', 'pernikahan', 'perkawinan', 'kepegawaian',
        // bahasa Inggris
        'name', 'surname', 'firstname', 'lastname', 'phone', 'mobile', 'address', 'age', 'birthday', 'birth', 'sex',
        'school', 'university', 'company', 'organization', 'department', 'position', 'job', 'occupation', 'city',
        'province', 'country', 'zip', 'postal', 'employee', 'staff', 'student',
    ],

    // Kata pelengkap yang boleh menyertai kata identitas. Tidak cukup sendirian untuk menandai identitas.
    'fillers' => [
        'anda', 'kamu', 'saya', 'mu', 'kami', 'lengkap', 'panggilan', 'depan', 'belakang', 'tengah', 'pertama',
        'terakhir', 'sekarang', 'saat', 'ini', 'sesuai', 'mahasiswa', 'siswa', 'peserta', 'responden', 'pengisi',
        'no', 'nomor', 'kode', 'jenis', 'tempat', 'tanggal', 'bulan', 'tahun', 'asal', 'aktif', 'yang', 'bisa',
        'dapat', 'dihubungi', 'digunakan', 'dan', 'atau', 'di', 'ke', 'pada', 'untuk', 'dengan', 'dari', 'tinggal',
        'rumah', 'ktm', 'kartu', 'tanda', 'pengenal', 'penduduk', 'keluarga', 'program', 'mohon', 'harap',
        'silakan', 'tolong', 'isi', 'isikan', 'tulis', 'tuliskan', 'masukkan', 'ketik',
        // bahasa Inggris
        'your', 'my', 'full', 'first', 'last', 'middle', 'the', 'of', 'number', 'current', 'e', 'and', 'or',
        'please', 'enter', 'write', 'date',
    ],

];