# Product Requirement Document: Essay Grader

*Penilai esai otomatis berbasis Laravel, terintegrasi Google Form dan Groq AI. Disusun oleh Lead Technical Writer & Product Architect. Versi 1.0 (Draft untuk review).*

## 1. Dokumen Metadata & Versi

| Atribut | Nilai |
| --- | --- |
| Nama Produk | Essay Grader |
| Kode Dokumen | PRD-EG-001 |
| Versi | 1.0 |
| Status | Draft untuk review stakeholder |
| Product Owner | (diisi tim) |
| Tech Lead | (diisi tim) |
| Stack Utama | Laravel (PHP), MySQL/PostgreSQL, Redis (queue & cache), Google Forms API, Groq Cloud API |
| Audiens Dokumen | Product, Engineering, QA, Security, Pemangku kepentingan akademik |
| Tanggal | 18 November 2026 |

### Riwayat Revisi

| Versi | Tanggal | Penulis | Perubahan |
| --- | --- | --- | --- |
| 0.1 | 2026-11-10 | Tim Produk | Kerangka awal |
| 1.0 | 2026-11-18 | Lead Tech Writer | PRD lengkap pertama |

### Glosarium Singkat

- **BYOK**: Bring Your Own Key, pengguna memakai API key Groq pribadinya.
- **HITL**: Human-in-the-Loop, verifikasi manual oleh pendidik.
- **Short answer**: jawaban pendek (satu kata, angka, frasa, istilah) yang dinilai deterministik.
- **Full paragraph**: jawaban esai berparagraf yang dinilai AI dengan tiga parameter.
- **Idempotensi**: pemrosesan ulang jawaban yang sama tidak menghasilkan duplikasi data atau penilaian ganda.

## 2. Eksekutif Ringkasan & Problem Statement

### 2.1 Ringkasan Eksekutif

Essay Grader adalah aplikasi web yang mengotomatisasi koreksi jawaban esai dan isian singkat dari Google Form. Sistem menggabungkan dua pendekatan: **heuristik regex deterministik** untuk jawaban pendek yang kunci jawabannya jelas, dan **evaluasi AI multi-parameter** (Groq API) untuk jawaban paragraf. Hasil otomatis selalu dapat ditinjau, diubah, dan dikembalikan ke nilai awal oleh pendidik, sehingga AI berperan sebagai asisten, bukan penentu akhir.

### 2.2 Problem Statement

1. **Tantangan koreksi massal.** Satu ujian dengan 100 mahasiswa dan 5 soal esai menghasilkan 500 jawaban yang harus dibaca satu per satu. Pada periode ujian akhir, beban ini menumpuk di banyak mata kuliah sekaligus.
2. **Inkonsistensi penilaian manusiawi.** Kelelahan, urutan koreksi, dan bias halo membuat jawaban setara dapat menerima nilai berbeda. Konsistensi antar-koreksi (misalnya dosen dan asisten) sulit dijaga tanpa rubrik yang diterapkan seragam.
3. **Pemborosan waktu pendidik.** Waktu yang seharusnya dipakai untuk umpan balik bermakna dan perbaikan pengajaran habis untuk pekerjaan repetitif, terutama pada jawaban pendek yang sebenarnya dapat dicek secara otomatis.
4. **Fragmentasi data.** Jawaban tersebar di Google Form dan spreadsheet, tanpa identitas siswa yang terstandar, tanpa rekap distribusi nilai, dan tanpa jejak audit perubahan nilai.

### 2.3 Dampak yang Diharapkan

Penurunan waktu koreksi, penilaian yang konsisten berdasarkan rubrik tertulis, dan data hasil ujian yang rapi dan dapat diekspor, dengan kontrol akhir tetap pada pendidik.

## 3. Visi Produk, Sasaran Strategis, dan Metrik Keberhasilan

### 3.1 Visi Produk

> Menjadikan koreksi esai cepat, konsisten, dan transparan, sehingga pendidik fokus pada pembelajaran, bukan pada pekerjaan administratif.

### 3.2 Sasaran Strategis

1. Mengurangi beban koreksi manual secara signifikan tanpa mengorbankan kualitas penilaian.
2. Menjamin transparansi: setiap nilai AI disertai alasan per parameter dan dapat diaudit.
3. Menjaga kepercayaan dan privasi data ujian (multi-tenant yang terisolasi, akses Google read-only).
4. Tetap andal di bawah batasan kuota Google Forms API dan token limit Groq.

### 3.3 OKR

**Objective 1: Menghemat waktu koreksi pendidik.**

| Key Result | Target |
| --- | --- |
| KR1.1 Pengurangan waktu koreksi rata-rata per ujian dibanding manual (survei pengguna beta) | ≥ 60% |
| KR1.2 Persentase jawaban short answer yang dinilai tanpa panggilan AI | ≥ 90% |
| KR1.3 Waktu dari pengguna menautkan form hingga struktur form terdeteksi | ≤ 60 detik |

**Objective 2: Penilaian akurat dan dapat dipercaya.**

| Key Result | Target |
| --- | --- |
| KR2.1 Korelasi (Pearson) nilai AI vs nilai pendidik pada set uji beranotasi | ≥ 0,85 |
| KR2.2 Selisih nilai AI vs pendidik dalam ±10 poin (skala 100) | ≥ 85% jawaban |
| KR2.3 Akurasi klasifikasi identitas (nama, NIM, email) | ≥ 95% F1 |
| KR2.4 Tingkat override manual pada jawaban yang sudah dinilai (indikator, dipantau tren) | < 25% |

**Objective 3: Operasional andal.**

| Key Result | Target |
| --- | --- |
| KR3.1 Jawaban berstatus *graded* atau *needs\_review* (bukan *failed* permanen) | ≥ 99% |
| KR3.2 Duplikasi jawaban akibat sinkronisasi ulang | 0 |
| KR3.3 Ketersediaan layanan web (bulanan) | ≥ 99,5% |

### 3.4 Target Latency

| Operasi | Target |
| --- | --- |
| Penilaian short answer (regex) | p95 < 200 ms per jawaban |
| Satu batch AI (5 esai/request) | p95 < 20 detik |
| Sinkronisasi form hingga jawaban baru muncul di dashboard | ≤ 2 siklus polling (± 2 menit) |
| Muat halaman hasil ujian (≤ 500 jawaban) | p95 < 2 detik |
| Throughput per worker (3 request/menit × 5 esai) | 15 esai/menit |

Contoh kapasitas: 500 esai ÷ 15 esai/menit ≈ 34 menit dengan satu worker; skala horizontal dapat memangkas waktu ini selama kuota Groq mencukupi.

## 4. Persona Pengguna & User Stories

### 4.1 Persona

| Persona | Profil | Kebutuhan Utama | Frustrasi |
| --- | --- | --- | --- |
| **Dr. Rina, Dosen** | Mengampu 3 kelas, 100+ mahasiswa per kelas | Rubrik konsisten, kontrol akhir nilai, rekap cepat | Waktu koreksi menumpuk di akhir semester |
| **Pak Budi, Guru** | Guru SMA, ujian harian via Google Form | Setup cepat, kunci jawaban pendek otomatis | Tidak punya waktu belajar tool rumit |
| **Sinta, Instruktur Ujian** | Menyelenggarakan ujian sertifikasi internal | Isolasi data antar ujian, audit nilai | Kekhawatiran privasi dan kebocoran data |
| **Andi, Asisten Lab** | Membantu dosen mengoreksi laporan dan kuis | Antrian review yang jelas, filter jawaban bermasalah | Sulit tahu jawaban mana yang perlu perhatian |

### 4.2 User Stories & Acceptance Criteria

**US-01. Registrasi dan login.** *Sebagai pendidik, saya ingin masuk dengan email/password atau Google agar akses akun mudah.*

- **Given** pengguna belum punya akun, **When** mendaftar dengan email valid dan password memenuhi kebijakan, **Then** akun dibuat dan pengguna diarahkan ke onboarding.
- **Given** pengguna menekan "Masuk dengan Google", **When** persetujuan OAuth diberikan, **Then** akun dibuat atau dihubungkan berdasarkan email terverifikasi.
- **Given** kredensial salah 5 kali berturut-turut, **When** percobaan berikutnya dilakukan, **Then** login dibatasi sementara (throttle).

**US-02. BYOK Groq.** *Sebagai pendidik, saya ingin memakai API key Groq pribadi agar biaya dan kuota terpisah.*

- **Given** pengguna membuka Pengaturan, **When** memasukkan API key dan menyimpan, **Then** key divalidasi dengan panggilan uji ringan dan disimpan terenkripsi.
- **Given** key sudah tersimpan, **When** halaman pengaturan dimuat ulang, **Then** key hanya tampil dalam bentuk tersamarkan (4 karakter terakhir).
- **Given** key tidak valid, **When** divalidasi, **Then** sistem menampilkan pesan galat jelas dan tidak menyimpan key.

**US-03. Menautkan Google Form.** *Sebagai pendidik, saya ingin menautkan form agar sistem memahami strukturnya.*

- **Given** akun Google terhubung dengan scope read-only, **When** pengguna memilih form, **Then** sistem membaca struktur dan mengklasifikasikan tiap pertanyaan (tipe field dan peran).
- **Given** hasil deteksi, **When** pengguna membuka pratinjau, **Then** pengguna dapat mengoreksi peran field (identitas/short answer/paragraf/abaikan) sebelum penilaian dimulai.

**US-04. Mengatur kunci dan rubrik.** *Sebagai pendidik, saya ingin menentukan kunci jawaban dan rubrik per soal.*

- **Given** soal bertipe short answer, **When** pengguna mengisi satu atau lebih kunci/pola yang diterima, **Then** sistem menyimpan aturan pencocokan (exact, normalisasi, regex).
- **Given** soal paragraf, **When** pengguna mengisi jawaban acuan dan bobot parameter, **Then** total bobot harus 100% sebelum disimpan.

**US-05. Sinkronisasi otomatis.** *Sebagai pendidik, saya ingin jawaban baru masuk otomatis.*

- **Given** form aktif dengan polling berkala, **When** ada respons baru di Google Form, **Then** respons ditarik, disimpan, dan masuk antrean penilaian.
- **Given** respons yang sama terambil dua kali, **When** sinkronisasi berjalan, **Then** tidak ada duplikasi data maupun penilaian ganda.
- **Given** kuota Google API tercapai, **When** respons 429 diterima, **Then** sinkronisasi menunda diri (backoff) dan status ditampilkan di dashboard.

**US-06. Penilaian hybrid.** *Sebagai pendidik, saya ingin short answer dinilai cepat dan esai dinilai AI dengan alasan.*

- **Given** jawaban short answer, **When** diproses, **Then** nilai ditentukan oleh aturan deterministik tanpa memanggil AI.
- **Given** jawaban paragraf, **When** dinilai, **Then** hasil memuat skor untuk tiga parameter, skor total berbobot, dan alasan singkat per parameter.
- **Given** output AI tidak sesuai skema JSON, **When** divalidasi, **Then** jawaban ditandai *failed* dan masuk jalur retry.

**US-07. Review manual.** *Sebagai pendidik, saya ingin mengubah nilai AI bila tidak setuju.*

- **Given** jawaban sudah dinilai, **When** pengguna mengubah skor dan menyimpan, **Then** nilai final berubah, tanda *overridden* muncul, dan nilai AI asli tetap tersimpan.
- **Given** jawaban berstatus *overridden*, **When** pengguna menekan "Kembalikan", **Then** nilai final kembali ke nilai AI/aturan awal.
- **Given** jawaban berstatus *failed*, **When** pengguna menekan "Coba lagi", **Then** jawaban masuk kembali ke antrean penilaian.

**US-08. Pelaporan dan ekspor.** *Sebagai pendidik, saya ingin melihat sebaran nilai dan mengekspor hasil.*

- **Given** ujian memiliki jawaban ternilai, **When** membuka tab Laporan, **Then** tampil statistik (rata-rata, median, min, maks, simpangan baku) dan histogram distribusi.
- **Given** pengguna menekan Ekspor, **When** memilih CSV atau Excel, **Then** berkas berisi identitas, skor per soal, skor total, status review, dan waktu penilaian.

**US-09. Privasi akun.** *Sebagai pengguna, saya ingin mengendalikan data saya.*

- **Given** pengguna meminta hapus akun, **When** dikonfirmasi dengan password/re-auth, **Then** akun masuk status *pending deletion* selama 14 hari dan dapat dibatalkan.
- **Given** masa tenggang 14 hari berakhir, **When** job pembersihan berjalan, **Then** seluruh data pengguna dan token dihapus permanen.
- **Given** pengguna meminta ekspor data diri, **When** diproses, **Then** pengguna menerima berkas berisi profil, form, dan hasil miliknya.

## 5. Ruang Lingkup Fungsional

### 5.1 In-Scope dan Out-of-Scope

| In-Scope (v1.0) | Out-of-Scope (v1.0) |
| --- | --- |
| Login email/password dan Google OAuth | SSO institusi (SAML/LDAP) |
| BYOK Groq per pengguna | Pembelian kredit AI/billing internal |
| Impor struktur Google Form (read-only) | Penulisan balik nilai ke Google Form/Classroom |
| Penilaian short answer dan paragraf | Pilihan ganda/kuis otomatis (sudah ditangani Google Form) |
| Review manual, override, revert, retry | Penilaian jawaban gambar, berkas, atau tulisan tangan |
| Laporan, grafik distribusi, ekspor CSV/Excel | Deteksi plagiarisme dan deteksi teks buatan AI |
| Penghapusan akun 14 hari dan ekspor data | Integrasi LMS (Moodle, Canvas) dan aplikasi mobile native |
| Antarmuka Bahasa Indonesia (i18n siap) | Kolaborasi multi-dosen pada satu ujian (roadmap) |

### 5.2 Modul Autentikasi & BYOK

| ID | Persyaratan | Prioritas |
| --- | --- | --- |
| FR-AUTH-01 | Registrasi dan login email/password dengan hashing bcrypt/argon2, verifikasi email, dan reset password. | Must |
| FR-AUTH-02 | Login/registrasi Google OAuth 2.0; penautan akun berdasarkan email terverifikasi. | Must |
| FR-AUTH-03 | Akses Google Forms diminta terpisah (incremental authorization) hanya dengan scope read-only. | Must |
| FR-AUTH-04 | Pengguna menyimpan Personal Groq API Key; key divalidasi saat disimpan, dienkripsi, dan tidak pernah ditampilkan utuh. | Must |
| FR-AUTH-05 | Pengguna dapat memutus koneksi Google dan menghapus/mengganti Groq key kapan saja; pencabutan menghentikan sinkronisasi. | Must |
| FR-AUTH-06 | Penilaian AI diblokir dengan pesan jelas bila key belum diisi; short answer tetap berjalan. | Should |

### 5.3 Modul Ingesti & Analisis Struktur Form

| ID | Persyaratan | Prioritas |
| --- | --- | --- |
| FR-ING-01 | Mengambil struktur form (judul, pertanyaan, tipe item) melalui Google Forms API. | Must |
| FR-ING-02 | **Auto-detection tipe field**: short text, paragraph, pilihan, skala, tanggal, dan lainnya dipetakan ke tipe internal. | Must |
| FR-ING-03 | **Auto-detection peran field**: *identity\_name*, *identity\_id* (NIM/NIS), *identity\_email*, *short\_answer*, *essay*, *ignored*. Menggunakan heuristik judul pertanyaan, tipe field, dan pola nilai sampel. | Must |
| FR-ING-04 | Menampilkan skor keyakinan deteksi dan memungkinkan koreksi manual sebelum penilaian. | Must |
| FR-ING-05 | Mendeteksi perubahan struktur form (soal ditambah/dihapus) dan meminta konfirmasi pemetaan ulang tanpa menghapus data lama. | Should |
| FR-ING-06 | Identitas siswa dinormalisasi (trim, kapitalisasi, format NIM) untuk penggabungan beberapa pengiriman oleh siswa yang sama. | Should |

### 5.4 Modul Sinkronisasi Berkala & Idempotensi

| ID | Persyaratan | Prioritas |
| --- | --- | --- |
| FR-SYNC-01 | Scheduler Laravel memicu job polling per form aktif dengan interval yang dapat dikonfigurasi (default 1 menit) dan tetap di bawah kuota Google Forms API. | Must |
| FR-SYNC-02 | Sinkronisasi inkremental memakai penanda waktu/ID respons terakhir. | Must |
| FR-SYNC-03 | **Idempotensi**: unique constraint pada (form\_id, google\_response\_id, question\_id); upsert menjamin pemrosesan ulang tidak menggandakan data atau penilaian. | Must |
| FR-SYNC-04 | Jika siswa mengirim ulang respons, sistem menyimpan versi terbaru sesuai kebijakan form (default: pengiriman terakhir dinilai, riwayat disimpan). | Should |
| FR-SYNC-05 | Penanganan 429/5xx dengan exponential backoff dan jitter; status sinkronisasi (terakhir sukses, galat) terlihat di dashboard. | Must |
| FR-SYNC-06 | Pengguna dapat menjeda, melanjutkan, atau memicu sinkronisasi manual (dengan throttle). | Should |

### 5.5 Modul Hybrid Grading Engine

**Jalur A: Short answer deterministik.**

| ID | Persyaratan | Prioritas |
| --- | --- | --- |
| FR-GRD-01 | Normalisasi: lowercase, trim, hapus tanda baca/spasi ganda, normalisasi angka dan satuan sederhana. | Must |
| FR-GRD-02 | Mode pencocokan: exact, daftar sinonim, dan regex yang dikonfigurasi pengguna. Regex divalidasi dan dibatasi waktu eksekusi untuk mencegah ReDoS. | Must |
| FR-GRD-03 | Skor biner atau parsial sesuai konfigurasi; hasil menyertakan aturan yang cocok sebagai alasan. | Must |

**Jalur B: Full paragraph dengan evaluasi 3 parameter.**

| ID | Persyaratan | Prioritas |
| --- | --- | --- |
| FR-GRD-04 | Tiga parameter default: **Ketepatan Konsep**, **Kelengkapan**, **Kejelasan & Penalaran**. Bobot default 50/30/20, dapat diubah per soal dengan total 100%. | Must |
| FR-GRD-05 | Prompt memuat soal, jawaban acuan/rubrik, dan jawaban siswa; output wajib JSON dengan skor per parameter, alasan singkat, dan flag keyakinan. | Must |
| FR-GRD-06 | Validasi skema ketat atas output; skor di luar rentang dipotong (clamp), output tak valid dianggap gagal. | Must |
| FR-GRD-07 | Skor total dihitung di sisi aplikasi (bukan oleh LLM) sebagai jumlah berbobot parameter. | Must |
| FR-GRD-08 | Jawaban dengan keyakinan rendah atau terindikasi injeksi otomatis ditandai *needs\_review*. | Should |
| FR-GRD-09 | Setiap penilaian menyimpan metadata: model, versi prompt, token terpakai, dan durasi, untuk audit dan reproduksibilitas. | Should |

### 5.6 Modul Human-in-the-Loop Review

| ID | Persyaratan | Prioritas |
| --- | --- | --- |
| FR-HITL-01 | Antrean review dengan filter: *needs\_review*, *failed*, *overridden*, rentang skor, dan pencarian siswa. | Must |
| FR-HITL-02 | **Override**: edit skor per parameter atau total beserta catatan opsional; nilai AI asli tidak pernah ditimpa. | Must |
| FR-HITL-03 | **Revert**: mengembalikan ke nilai otomatis awal dalam satu aksi. | Must |
| FR-HITL-04 | **Retry**: menjadwalkan ulang jawaban *failed* (individual dan massal) ke antrean. | Must |
| FR-HITL-05 | Jejak audit perubahan nilai (siapa, kapan, nilai lama, nilai baru). | Must |
| FR-HITL-06 | Tombol "Nilai ulang" untuk satu jawaban setelah rubrik diubah, dengan konfirmasi bahwa override sebelumnya dapat tertimpa. | Could |

### 5.7 Modul Pelaporan & Visualisasi

| ID | Persyaratan | Prioritas |
| --- | --- | --- |
| FR-RPT-01 | Ringkasan ujian: jumlah peserta, persentase terkoreksi, rata-rata, median, min, maks, simpangan baku. | Must |
| FR-RPT-02 | Grafik distribusi nilai (histogram) dan sebaran per soal. | Must |
| FR-RPT-03 | Analisis butir soal: rata-rata skor per soal dan persentase jawaban kosong. | Should |
| FR-RPT-04 | Ekspor CSV dan Excel (.xlsx) dengan kolom identitas, skor per soal, skor total, status, dan penanda override. Nilai sel yang diawali karakter `=`, `+`, `-`, `@` dinetralkan untuk mencegah CSV/formula injection. | Must |
| FR-RPT-05 | Ekspor dijalankan sebagai job asinkron untuk ujian besar dan dapat diunduh dengan tautan bertanda tangan berumur pendek. | Should |

### 5.8 Modul Privasi Akun & Lifecycle

| ID | Persyaratan | Prioritas |
| --- | --- | --- |
| FR-PRV-01 | Permintaan hapus akun memerlukan re-autentikasi; akun masuk status *pending deletion* dengan **masa tenggang 14 hari**. | Must |
| FR-PRV-02 | Selama masa tenggang, sinkronisasi dan penilaian dijeda, login memunculkan opsi pembatalan, dan notifikasi email dikirim pada hari ke-0 dan ke-13. | Must |
| FR-PRV-03 | Setelah 14 hari, job terjadwal menghapus permanen akun, form, jawaban, nilai, token OAuth, dan Groq key. | Must |
| FR-PRV-04 | Ekspor data diri (profil, form, jawaban, nilai) dalam format JSON/CSV dalam arsip ZIP, dibuat asinkron dan kedaluwarsa otomatis. | Must |
| FR-PRV-05 | Pada penghapusan, cabut token Google (revoke) dan hapus data dari cadangan sesuai kebijakan retensi yang didokumentasikan. | Should |

## 6. Persyaratan Non-Fungsional (NFR)

### 6.1 Performance

| ID | Persyaratan |
| --- | --- |
| NFR-PERF-01 | **Batch processing**: jawaban paragraf dikirim ke Groq dalam batch maksimal **5 esai per request** (dengan batasan token input per batch). |
| NFR-PERF-02 | **Rate limiting**: maksimal **3 request/menit per worker** ke Groq, ditegakkan lewat rate limiter Laravel/Redis. |
| NFR-PERF-03 | Job penilaian berjalan di queue terpisah dari job sinkronisasi dan ekspor agar tidak saling memblokir. |
| NFR-PERF-04 | Indeks database pada (user\_id, form\_id, status) dan (form\_id, google\_response\_id); halaman daftar menggunakan pagination sisi server. |
| NFR-PERF-05 | Batas panjang jawaban per esai (misalnya 4.000 karakter) agar batch tidak melampaui token limit; jawaban lebih panjang dipotong dengan penanda atau dinilai tunggal. |

### 6.2 Security

| ID | Persyaratan |
| --- | --- |
| NFR-SEC-01 | **Enkripsi token OAuth** (access dan refresh token) dan Groq API key di database menggunakan enkripsi aplikasi (AES-256 via `APP_KEY`/encrypted cast); rotasi kunci didokumentasikan. |
| NFR-SEC-02 | **Isolasi multi-tenant**: seluruh query dibatasi `user_id` melalui global scope dan Laravel Policy; uji otomatis memastikan tidak ada akses lintas pengguna (IDOR). |
| NFR-SEC-03 | Seluruh trafik via HTTPS/TLS 1.2+, cookie `Secure`, `HttpOnly`, `SameSite`, proteksi CSRF, dan header keamanan (CSP, HSTS). |
| NFR-SEC-04 | Rate limiting login dan endpoint sensitif; log tidak boleh memuat token, API key, atau isi jawaban lengkap. |
| NFR-SEC-05 | Tautan unduhan ekspor bertanda tangan dan berumur pendek; berkas dihapus setelah kedaluwarsa. |

### 6.3 Reliability & Resilience

| ID | Persyaratan |
| --- | --- |
| NFR-REL-01 | **Auto-cooldown pada 429**: saat Groq mengembalikan 429, worker membaca header retry/reset, menunda antrean sesuai nilai tersebut (backoff + jitter), dan tidak menghitungnya sebagai kegagalan penilaian. |
| NFR-REL-02 | **Fallback model LLM**: model utama dan model cadangan dapat dikonfigurasi; bila model utama gagal berulang atau tidak tersedia, penilaian beralih ke model cadangan dan model yang dipakai dicatat. |
| NFR-REL-03 | **Dead-letter retry**: job yang melampaui batas percobaan dipindah ke tabel dead-letter (failed jobs), jawaban berstatus *failed*, dapat dicoba ulang manual atau massal dari UI. |
| NFR-REL-04 | Semua job idempoten dan aman dijalankan ulang; penggunaan lock untuk mencegah dua worker menilai jawaban yang sama. |
| NFR-REL-05 | Circuit breaker: bila tingkat galat penyedia melewati ambang, penilaian dijeda dan pengguna diberi notifikasi status. |
| NFR-REL-06 | Pencadangan basis data harian dengan retensi terdefinisi dan prosedur pemulihan teruji. |
| NFR-REL-07 | Observabilitas: metrik antrean, latensi, tingkat galat, token terpakai, dan peringatan otomatis. |

### 6.4 Compliance & Privacy

| ID | Persyaratan |
| --- | --- |
| NFR-CMP-01 | **Google API read-only**: hanya scope baca (struktur form dan respons); aplikasi tidak pernah menulis ke form atau Drive pengguna. Scope diminta seminimal mungkin (least privilege) dan wajib lolos verifikasi OAuth Google. |
| NFR-CMP-02 | **Perlindungan prompt injection**: jawaban siswa selalu diperlakukan sebagai data, bukan instruksi. Langkah: (a) pembatas eksplisit dan instruksi sistem yang menyatakan isi jawaban tidak boleh mengubah aturan penilaian; (b) output wajib JSON berskema, tanpa teks bebas yang dieksekusi; (c) skor total dihitung di aplikasi; (d) deteksi pola injeksi (misalnya "abaikan instruksi sebelumnya") yang menandai jawaban *needs\_review*; (e) nilai yang sangat tinggi dengan alasan janggal ditandai untuk ditinjau. |
| NFR-CMP-03 | Kepatuhan terhadap UU Pelindungan Data Pribadi (UU PDP) Indonesia dan, bila relevan, GDPR: dasar pemrosesan, minimisasi data, hak akses, hak penghapusan, dan retensi terdokumentasi. |
| NFR-CMP-04 | Kebijakan Privasi dan Syarat Layanan menjelaskan bahwa teks jawaban dikirim ke Groq untuk dinilai, dan pendidik bertanggung jawab atas persetujuan siswa sesuai kebijakan institusinya. |
| NFR-CMP-05 | Data yang dikirim ke LLM diminimalkan: identitas siswa (nama, NIM, email) tidak disertakan dalam prompt; hanya ID anonim internal dan teks jawaban. |
| NFR-CMP-06 | Kepatuhan Google API Services User Data Policy (Limited Use). |

### 6.5 Usability & Maintainability (Ringkas)

- Antarmuka responsif, Bahasa Indonesia, memenuhi WCAG 2.1 AA pada alur utama.
- Cakupan uji otomatis minimal 80% pada layanan grading dan sinkronisasi; CI menjalankan analisis statis dan uji keamanan dependensi.

## 7. Asumsi, Dependensi Pihak Ketiga, dan Risiko Teknis

### 7.1 Asumsi

1. Pendidik memiliki akun Google dan hak edit/baca atas form yang ditautkan.
2. Pendidik bersedia membuat dan mengelola API key Groq sendiri (BYOK).
3. Jawaban dalam Bahasa Indonesia dan/atau Inggris; bahasa lain bukan target akurasi v1.0.
4. Skala nilai default 0-100; konversi ke skala lain dilakukan saat ekspor.
5. Ukuran ujian tipikal 20-300 peserta dengan 1-10 soal esai.
6. Form menyertakan field identitas (nama dan/atau NIM) agar jawaban dapat dikaitkan ke siswa.
7. Infrastruktur menyediakan Redis dan proses queue worker yang berjalan terus-menerus (Supervisor/Horizon).

### 7.2 Dependensi Pihak Ketiga

| Dependensi | Fungsi | Dampak bila terganggu |
| --- | --- | --- |
| Google Forms API & Google OAuth | Struktur form, respons, autentikasi | Sinkronisasi berhenti; data lama tetap dapat ditinjau |
| Groq Cloud API | Evaluasi AI paragraf | Penilaian paragraf tertunda; short answer tetap berjalan |
| Laravel & ekosistem (Queue, Scheduler, Socialite) | Kerangka aplikasi | Perlu pembaruan keamanan berkala |
| Redis | Antrean, cache, rate limiter | Penilaian dan sinkronisasi terganggu |
| Layanan email (SMTP/transaksional) | Verifikasi, reset password, notifikasi penghapusan | Alur akun terhambat |
| Pustaka ekspor (misalnya Laravel Excel) | CSV/Excel | Fitur ekspor terganggu |

### 7.3 Risiko Teknis & Mitigasi

| ID | Risiko | Probabilitas | Dampak | Mitigasi |
| --- | --- | --- | --- | --- |
| R-01 | Kuota Google Forms API terlampaui saat banyak form aktif | Sedang | Tinggi | Polling adaptif, backoff, penjadwalan tersebar, cache struktur form, antrean terpusat |
| R-02 | Token limit atau rate limit Groq membatasi throughput saat ujian serentak | Tinggi | Tinggi | Batch 5 esai, limiter 3 req/menit/worker, auto-cooldown, antrean prioritas, estimasi waktu selesai di UI |
| R-03 | Perubahan model/deprecation di Groq mengubah kualitas atau ketersediaan | Sedang | Sedang | Model terkonfigurasi, fallback, evaluasi regresi pada set uji emas |
| R-04 | Halusinasi atau penilaian bias oleh LLM | Sedang | Tinggi | Rubrik eksplisit, jawaban acuan, validasi skema, HITL, flag keyakinan, audit |
| R-05 | Prompt injection oleh siswa | Sedang | Tinggi | Lihat NFR-CMP-02, tinjauan otomatis, pengujian red-team |
| R-06 | Salah klasifikasi peran field (misalnya NIM dikira jawaban) | Sedang | Sedang | Skor keyakinan, pratinjau, koreksi manual, set uji F1 ≥ 95% |
| R-07 | Kebocoran token atau data antar-tenant | Rendah | Kritis | Enkripsi, policy dan global scope, uji IDOR, audit log, pemindaian dependensi |
| R-08 | ReDoS pada regex buatan pengguna | Rendah | Sedang | Validasi pola, batas waktu dan panjang input, pustaka regex aman |
| R-09 | Verifikasi OAuth Google (scope sensitif) memakan waktu | Sedang | Sedang | Mulai proses lebih awal, mode pengujian terbatas selama beta |
| R-10 | Ketidakpercayaan pendidik terhadap nilai AI | Sedang | Sedang | Transparansi alasan per parameter, kontrol override, panduan kalibrasi pada beberapa sampel awal |
| R-11 | Kekhawatiran etika dan hukum penggunaan AI pada penilaian | Sedang | Sedang | Penegasan bahwa nilai akhir adalah keputusan pendidik, dokumentasi kebijakan, jejak audit |

### 7.4 Pertanyaan Terbuka

1. Apakah v1.1 perlu mendukung kalibrasi rubrik otomatis dari beberapa jawaban yang sudah dinilai manual (few-shot)?
2. Berapa lama data jawaban disimpan setelah ujian selesai (kebijakan retensi default)?
3. Apakah perlu mode antrean prioritas bagi ujian yang mendekati tenggat pengumuman nilai?

---

*Akhir dokumen PRD-EG-001 v1.0.*
