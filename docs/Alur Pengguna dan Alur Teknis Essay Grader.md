# Alur Pengguna dan Alur Teknis: Essay Grader

*Dokumentasi User Flow dan Technical Workflow seluruh siklus hidup interaksi aplikasi. Disusun oleh Senior Technical Systems Analyst. Versi 1.0 (Draft), turunan dari PRD, System Architecture Document, dan Spesifikasi Skema Database.*

## 1. Cara Membaca Dokumen

Dokumen ini memuat delapan diagram Mermaid: satu *flowchart* perjalanan pengguna (Bagian 2) dan tujuh *sequence diagram* untuk alur teknis (Bagian 3 sampai 6). Setiap diagram diikuti aturan dan kasus galat yang tidak muat di gambar. Nama kelas, tabel, dan kolom mengikuti dokumen sebelumnya; selisih yang ditemukan dikumpulkan di Bagian 7.

## 2. End-to-End User Journey

Perjalanan pendidik dari login hingga unduh Excel terbagi empat fase. Fase 3 berjalan otomatis tanpa tindakan pengguna; garis putus-putus menandai jalur berulang atau pengecualian.

```mermaid
flowchart TD
    A(["Mulai"]) --> B{"Sudah punya akun?"}
    B -->|"Belum"| C["Daftar: email dan password, atau Google"]
    B -->|"Sudah"| D["Login"]
    C --> E["Dashboard"]
    D --> E

    subgraph F1["Fase 1: Koneksi"]
        G{"Google sudah terhubung?"}
        H["Klik Hubungkan Google, setujui izin read-only"]
        I{"Izin forms diberikan?"}
        J["Simpan Groq API key, divalidasi otomatis"]
    end
    E --> G
    G -->|"Belum"| H
    G -->|"Sudah"| J
    H --> I
    I -->|"Tidak"| H
    I -->|"Ya"| J

    subgraph F2["Fase 2: Impor dan konfigurasi"]
        K["Tempel URL Google Form"]
        L{"URL valid dan form dapat diakses?"}
        M["Analisis struktur otomatis: heuristik, AI untuk yang ambigu"]
        N["Pratinjau: koreksi peran field"]
        O["Konfigurasi rubrik: kunci jawaban, bobot, mode nilai"]
        P{"Konfigurasi valid? Bobot total 100 persen"}
    end
    J --> K
    K --> L
    L -->|"Tidak"| K
    L -->|"Ya"| M
    M --> N
    N --> O
    O --> P
    P -->|"Belum"| O
    P -->|"Valid"| Q["Aktifkan ujian: status active"]

    subgraph F3["Fase 3: Otomatis di latar belakang"]
        R["Scheduler tiap menit memicu sinkronisasi"]
        S["Jawaban baru disimpan, status pending"]
        T["Worker menilai: kosong 0, short answer, paragraf AI"]
        U{"Hasil penilaian"}
    end
    Q --> R
    R --> S
    S --> T
    T --> U
    U -.->|"jawaban baru dari siswa"| R
    R -.->|"token Google dicabut"| AF["Banner: hubungkan ulang Google"]
    AF -.-> H

    subgraph F4["Fase 4: Reviu dan laporan"]
        V["Dosen meninjau hasil dan antrean review"]
        W{"Perlu koreksi?"}
        X["Override skor atau beri catatan"]
        Y{"Batalkan perubahan?"}
        Z["Revert ke nilai AI"]
        AA["Lihat laporan: statistik dan grafik sebaran"]
        AB["Unduh Excel"]
    end
    U -->|"graded atau needs_review"| V
    U -.->|"failed: Coba lagi"| T
    V --> W
    W -->|"Ya"| X
    W -->|"Tidak"| AA
    X --> Y
    Y -->|"Ya"| Z
    Z --> V
    Y -->|"Tidak"| AA
    AA --> AB
    AB --> AC(["Selesai"])
```

## 3. Integrasi Google OAuth dan Izin Form

Hubungkan Google meminta izin *read-only* terpisah dari login: `forms.body.readonly` dan `forms.responses.readonly`. Login dengan Google hanya memakai identitas dasar (`openid email profile`), sehingga pengguna yang hanya ingin masuk tidak diminta izin Forms (incremental authorization).

### 3.1 Handshake Authorization Code Grant

```mermaid
sequenceDiagram
    autonumber
    actor U as Pengguna
    participant L as Laravel
    participant GA as Google OAuth
    participant DB as MySQL
    U->>L: Klik Hubungkan Google
    L->>L: Buat state acak, simpan di sesi
    L-->>U: Redirect ke Google dengan client_id, redirect_uri, scope forms read-only, state, access_type offline, prompt consent
    U->>GA: Login dan setujui izin
    GA-->>U: Redirect ke callback dengan code dan state
    U->>L: GET callback
    L->>L: Validasi state sama dengan sesi
    alt state tidak cocok atau pengguna menolak
        L-->>U: Tolak dan tampilkan penjelasan izin
    else
        L->>GA: POST token endpoint, grant_type authorization_code, code, client_secret
        GA-->>L: access_token, refresh_token, expires_in, scope
        L->>L: Pastikan kedua scope forms read-only diberikan
        L->>L: Enkripsi kedua token dengan encrypted cast dan APP_KEY
        L->>DB: UPDATE users, google_token, google_refresh_token, google_token_expires_at
        L-->>U: Redirect ke dashboard, Google terhubung
    end
```

`access_type=offline` dan `prompt=consent` diperlukan agar Google selalu mengembalikan refresh token. Pertukaran `code` terjadi di server sehingga `client_secret` tidak pernah sampai ke browser. Jika pengguna menghapus centang salah satu scope di layar persetujuan, hasilnya diperlakukan sebagai penolakan: token tidak disimpan dan pengguna diminta mengulang dengan penjelasan izin mana yang kurang.

### 3.2 Pemakaian Token, Refresh, dan Pencabutan Izin

```mermaid
sequenceDiagram
    autonumber
    participant J as Job atau Controller
    participant GC as GoogleClient
    participant DB as MySQL
    participant GA as Google OAuth
    participant GF as Forms API
    actor U as Pengguna
    J->>GC: Minta klien untuk pengguna
    GC->>DB: Baca token, didekripsi otomatis oleh cast
    alt token kosong
        GC-->>J: GoogleNotConnected
    else access token masih berlaku lebih dari 60 detik
        Note over GC: Lanjut tanpa refresh
    else access token segera kedaluwarsa
        GC->>GA: POST token, grant_type refresh_token
        alt refresh berhasil
            GA-->>GC: access_token baru dan expires_in
            GC->>DB: Simpan token terenkripsi dan expires_at baru
        else invalid_grant
            GA-->>GC: 400 invalid_grant
            GC->>DB: Kosongkan google_token dan google_refresh_token
            GC-->>J: GoogleAuthRevoked
            J->>U: Banner dan email, hubungkan ulang Google
            Note over J,DB: Sinkronisasi ujian pengguna dijeda, data tetap aman
        end
    end
    GC->>GF: Panggil API dengan Bearer token
    alt 401 padahal belum kedaluwarsa
        GC->>GA: Paksa refresh satu kali lalu ulangi panggilan
    else 403 scope kurang
        GC-->>J: GoogleScopeMissing, minta persetujuan ulang
    else 200
        GF-->>GC: Data form atau respons
    end
```

### 3.3 Kondisi Galat OAuth

| Kondisi | Cara terdeteksi | Tindakan sistem | Tindakan pengguna |
| --- | --- | --- | --- |
| Pengguna mencabut akses di Google Account settings | Tidak ada notifikasi ke aplikasi; terdeteksi saat refresh (`invalid_grant`) atau API menjawab 401 | Kosongkan kedua token, jeda sinkronisasi, kirim banner dan email | Klik Hubungkan Google lagi |
| Refresh token kedaluwarsa (tidak dipakai lama, atau aplikasi OAuth berstatus Testing) | `invalid_grant` saat refresh | Sama seperti di atas | Hubungkan ulang |
| Salah satu scope dicabut | 403 dengan pesan izin tidak cukup | Jeda ujian terdampak, minta persetujuan ulang | Setujui scope yang kurang |
| Pengguna memutus koneksi dari aplikasi | Aksi eksplisit | Panggil endpoint revoke Google, lalu kosongkan token | Tidak ada |
| Gangguan jaringan atau 5xx Google | Exception transport | Backoff dan percobaan ulang job; token tidak disentuh | Tidak ada |

Status koneksi Google **diturunkan** dari token yang kosong, tanpa kolom status baru. Token tidak pernah ditulis ke log.

## 4. Ingesti dan Auto-Klasifikasi Form

```mermaid
sequenceDiagram
    autonumber
    actor U as Pengguna
    participant L as Laravel ExamImport
    participant V as Validator URL
    participant GC as GoogleClient
    participant GF as Forms API
    participant FA as FormAnalyzerService
    participant GQ as Groq lewat AiGradingService
    participant DB as MySQL
    U->>L: Tempel URL Google Form
    L->>V: Validasi dan ekstrak ID form
    alt URL tidak valid, tautan pendek, atau ID publik viewform
        V-->>L: Gagal
        L-->>U: Pesan galat spesifik, minta tautan edit
    else
        V-->>L: formId
        L->>DB: Cek exams untuk user_id dan gform_id
        alt ujian sudah ada
            L-->>U: Arahkan ke ujian yang ada
        else
            L->>GC: getForm(formId)
            GC->>GF: GET forms.get
            alt 404 atau 403
                GF-->>GC: Galat akses
                L-->>U: Form tidak ditemukan atau akun Google berbeda
            else 200
                GF-->>GC: info dan items
                GC-->>L: Struktur form
                L->>FA: analyze(items)
                loop setiap item form
                    FA->>FA: Petakan gform_item_type dan field_type awal
                    FA->>FA: IdentityFilter heuristik, cocokkan nama, nim, email, kelas, tanpa token
                end
                alt ada item ambigu dan Groq key tersedia
                    FA->>GQ: Kirim judul dan tipe item ambigu, tanpa data siswa
                    GQ-->>FA: JSON klasifikasi
                    FA->>FA: Validasi skema, item tidak valid tetap ambigu
                    FA->>DB: INSERT ai_usage_logs, exam_id NULL
                else tanpa key atau galat AI
                    FA->>FA: Item ambigu ditandai main dan perlu konfirmasi
                end
                FA-->>L: Daftar soal terklasifikasi dan tingkat keyakinan
                L->>DB: BEGIN
                L->>DB: INSERT exams, status draft, webhook_token acak
                L->>DB: UPSERT questions per exam_id dan gform_item_id
                L->>DB: COMMIT
                L-->>U: Halaman pratinjau struktur
            end
        end
    end
```

### 4.1 Validasi dan Ekstraksi ID Form

API Forms hanya menerima **ID form dari tautan edit**, bukan ID publik pada tautan `viewform`. Pola validator:

```text
^https://docs\.google\.com/forms/d/(?!e/)([A-Za-z0-9_-]{20,})(?:/[^?#]*)?(?:[?#].*)?$
```

| Masukan | Hasil |
| --- | --- |
| `https://docs.google.com/forms/d/ID/edit` | Diterima; ID = grup tangkapan 1 |
| `.../forms/d/ID/edit?usp=sharing` | Diterima |
| `.../forms/d/e/ID/viewform` | Ditolak: ID publik tidak dapat dipakai API; minta tautan edit |
| `https://forms.gle/...` | Ditolak: tautan pendek tidak diikuti untuk menghindari risiko redirect/SSRF |
| Host selain `docs.google.com` | Ditolak |

### 4.2 Pemetaan Tipe Item dan Heuristik IdentityFilter

| Item Google | Hasil awal |
| --- | --- |
| Pertanyaan teks pendek | Kandidat `identity` atau `main`, ditentukan heuristik |
| Pertanyaan paragraf | `main`, `scoring`; tidak pernah `identity` |
| Pilihan, skala, tanggal, waktu | `main` dengan `non_scoring` (tampil di ekspor, tidak dinilai) |
| Teks, gambar, video | `stimulus` |
| Pemisah halaman | Dilewati, tidak disimpan |

Judul dinormalisasi (huruf kecil, tanda baca dan spasi ganda dibuang) lalu dicocokkan:

| Kata kunci | Keputusan |
| --- | --- |
| nama, nama lengkap, name | `identity` (nama) |
| nim, npm, nis, nomor induk, student id | `identity` (ID siswa) |
| email, surel | `identity` (email) |
| kelas, class, rombel | `identity` (kelas) |
| identitas, data diri, kode, nomor, asal, angkatan, absen | Ambigu: diteruskan ke tahap AI |
| Tidak cocok | `main` (teks pendek di posisi awal form dianggap ambigu) |

Tahap AI hanya mengirim judul, deskripsi, dan tipe item, tidak pernah jawaban atau data siswa, dan tidak dijalankan di dalam transaksi database karena lambat. Hasil dengan keyakinan rendah tetap ditampilkan di pratinjau agar dikonfirmasi pendidik.

## 5. Sinkronisasi dan Penilaian Background Worker

### 5.1 Sinkronisasi Respons

```mermaid
sequenceDiagram
    autonumber
    participant C as Cron schedule run
    participant S as Command exams sync
    participant Q as Tabel jobs
    participant W as Worker SyncExamResponses
    participant DB as MySQL
    participant GC as GoogleClient
    participant GF as Forms API
    C->>S: Setiap menit
    S->>DB: Pilih ujian active, gform_deleted_at NULL, pemilik masih terhubung Google
    S->>Q: Dispatch SyncExamResponses per ujian, unik per exam_id
    Q->>W: Ambil job dari queue sync
    W->>DB: Baca exam, last_synced_at, questions
    W->>GC: listResponses dengan since = last_synced_at dikurangi 10 menit
    loop setiap halaman hasil
        GC->>GF: GET forms.responses.list dengan filter timestamp lebih besar atau sama dengan since
        GF-->>GC: Respons dan nextPageToken
    end
    alt 429 atau 5xx
        GC-->>W: Galat sementara
        W->>Q: Release job dengan backoff eksponensial dan jitter
    else 404 atau 403 pada form
        GC-->>W: Form hilang atau akses dicabut
        W->>DB: UPDATE exams gform_deleted_at
    else sukses
        W->>W: Petakan identitas dengan IdentityFilter, hitung identity_key
        W->>DB: BEGIN
        W->>DB: UPSERT submissions pada exam_id dan gform_response_id
        W->>DB: UPSERT student_answers pada submission_id dan question_id, status pending
        W->>DB: COMMIT
        W->>DB: UPDATE exams last_synced_at = waktu mulai run, setelah semua halaman sukses
        W->>Q: Dispatch ProcessEssayGrading bila ada pending
    end
```

| Aturan | Perilaku |
| --- | --- |
| Parameter `since` | Nilai internal `last_synced_at` dikurangi 10 menit, diterjemahkan ke parameter `filter` Google berbentuk `timestamp >= since` (RFC 3339 UTC). Sinkronisasi pertama tanpa filter |
| Tumpang tindih 10 menit | Menutup respons yang tercatat terlambat di sisi Google, selisih jam, dan sinkronisasi sebelumnya yang gagal. Respons yang terbaca ulang tidak berbahaya karena upsert idempoten |
| Pemajuan penanda | `last_synced_at` diisi **waktu mulai** run (bukan selesai) dan hanya bila seluruh halaman tersimpan, sehingga tidak ada celah |
| Jawaban tidak berubah | Baris dibiarkan; skor yang sudah ada tidak ditimpa |
| Jawaban diubah siswa | Kembali `pending` dan dinilai ulang; bila sudah di-override manual, ditandai `needs_review` |
| Duplikasi logis | Pencarian (`exam_id`, `identity_key`); respons baru dengan identitas sama mengikuti kebijakan pengiriman ulang |
| Field identity | Masuk ke `submissions.identity`, bukan `student_answers` |
| Soal `non_scoring` | Disimpan berstatus `graded` tanpa skor dan tidak pernah masuk antrean |
| Pencegahan paralel | Job unik per `exam_id`; satu ujian tidak disinkron dua kali bersamaan |

### 5.2 Eksekusi ProcessEssayGrading

```mermaid
sequenceDiagram
    autonumber
    participant Q as Tabel jobs
    participant W as Worker ProcessEssayGrading
    participant DB as MySQL
    participant SA as ShortAnswerGrader
    participant RL as RateLimiter ai-grading
    participant EG as EssayGraderService
    participant AI as AiGradingService
    participant GQ as Groq API
    Q->>W: Ambil job dari queue grading
    W->>DB: BEGIN, pilih pending FOR UPDATE SKIP LOCKED, set processing, COMMIT
    Note over W,DB: Batch esai 5 jawaban, short answer 40 jawaban
    W->>W: Partisi menjadi kosong, short answer, paragraf
    opt ada jawaban kosong
        W->>DB: UPDATE skor 0, final_score 0, graded, tanpa panggilan AI atau token
    end
    opt ada short answer
        W->>SA: Nilai jawaban terhadap answer_key
        SA->>SA: Normalisasi string lalu cocokkan kunci atau regex
        SA-->>W: Cocok atau tidak cocok
        W->>DB: UPDATE graded untuk yang cocok
        alt tidak cocok dan ai_fallback aktif
            Note over W,GQ: Masuk rute AI di bawah, lewat limiter
        else tidak cocok tanpa fallback
            W->>DB: UPDATE skor 0, graded
        end
    end
    opt ada paragraf atau fallback short answer
        W->>RL: attempt ai-grading, maksimal 3 request per menit
        alt jatah habis atau key cooldown
            RL-->>W: Ditolak dengan sisa detik
            W->>DB: Kembalikan ke pending, attempts dikurangi satu
            W->>Q: Release job dengan delay
        else jatah ada
            RL-->>W: Diizinkan
            W->>EG: Nilai batch maksimal 5 jawaban
            EG->>EG: Susun prompt dengan rubrik, bobot, jawaban dalam pembatas
            EG->>AI: Kirim prompt
            AI->>DB: Pilih key aktif, cek cooldown_until dan kuota harian
            AI->>GQ: POST chat completions mode JSON
            alt 429
                GQ-->>AI: 429 dengan retry-after
                AI->>DB: Set cooldown_until pada key
                W->>DB: Kembalikan ke pending tanpa menambah attempts
            else 5xx atau model tidak tersedia
                AI->>GQ: Ulangi dengan model fallback
            else 200
                GQ-->>AI: JSON array hasil
                AI-->>EG: Teks respons
                EG->>EG: Parsing, validasi per item, clamp 0 sampai 100
                EG->>EG: Hitung final_score berbobot
                EG-->>W: Hasil per item
                W->>DB: UPDATE bersyarat processing dan batch_id sama, simpan skor dan ai_original, set graded atau needs_review
                W->>DB: INSERT ai_usage_logs per jawaban
                Note over W,DB: Item tidak valid kembali pending, failed bila attempts habis
            end
        end
    end
    W->>Q: Dispatch ulang bila masih ada pending
```

Nilai akhir dihitung aplikasi, bukan model, dari tiga parameter dengan bobot per soal (`grading_config`, default 50, 30, 20) dan total bobot 1,00:

```latex
final = w_c \cdot s_{content} + w_l \cdot s_{logic} + w_s \cdot s_{structure}
```

| Jalur | Kondisi | Perlakuan | Token |
| --- | --- | --- | --- |
| Kosong | `answer_text` NULL atau hanya spasi | Semua skor 0, `graded`, sumber aturan | 0 |
| Short answer | Soal `main`, `scoring`, teks pendek | Normalisasi (huruf kecil, trim, buang tanda baca dan spasi ganda), cocok kunci atau regex memberi skor penuh, selain itu 0 atau fallback AI | 0, kecuali fallback |
| Paragraf | Soal paragraf | Batch maksimal 5, dikelompokkan per soal agar rubrik sama, satu request Groq | Ya |

Pemeriksaan limiter `ai-grading` berlaku pada setiap panggilan Groq (paragraf dan fallback short answer). Pekerjaan tanpa AI (jawaban kosong dan pencocokan deterministik) tidak pernah menunggu limiter.

## 6. Human Override dan Revert

```mermaid
sequenceDiagram
    autonumber
    actor D as Dosen
    participant L as Laravel ReviewController
    participant DB as MySQL student_answers
    D->>L: Ubah skor aspek atau final, tambah catatan
    L->>L: Policy, ujian milik pengguna ini
    L->>DB: BEGIN, SELECT jawaban FOR UPDATE
    alt processing_status = processing
        L-->>D: 409 sedang dinilai, coba lagi nanti
    else
        opt ai_original masih kosong
            L->>DB: Simpan snapshot skor saat ini ke ai_original, sekali saja
        end
        L->>L: Validasi 0 sampai 100, hitung ulang final_score bila yang diubah aspek
        L->>DB: UPDATE skor, final_score, is_overridden = 1, overridden_by, overridden_at, override_note
        L->>DB: COMMIT
        L-->>D: Nilai terbaru dengan penanda overridden
    end
    D->>L: Klik Revert
    L->>DB: BEGIN, SELECT jawaban FOR UPDATE
    alt ai_original kosong atau is_overridden = 0
        L-->>D: Tidak ada yang dikembalikan
    else
        L->>DB: UPDATE skor dan final_score dari ai_original
        L->>DB: UPDATE is_overridden = 0, overridden_by, overridden_at, override_note menjadi NULL
        L->>DB: COMMIT
        L-->>D: Nilai AI dipulihkan
    end
```

`ai_original` menyimpan potret yang tidak pernah ditimpa setelah terisi:

```json
{"source": "ai", "model": "nama-model", "scores": {"content": 80, "logic": 70, "structure": 90}, "final_score": 79, "reasons": {"content": "...", "logic": "...", "structure": "..."}, "captured_at": "2026-11-18T09:00:00Z"}
```

- **Sumber snapshot.** Normalnya `ai_original` terisi saat penilaian otomatis (`source` = `ai` atau `rule`). Snapshot saat override pertama hanya jaring pengaman bagi baris yang belum punya potret (`source` = `snapshot`).
- **Edit aspek vs final.** Mengubah satu aspek memicu perhitungan ulang `final_score` berbobot; mengubah `final_score` langsung diperbolehkan dan tidak mengubah aspek.
- **Konflik dengan worker.** Kunci baris dan penolakan 409 saat `processing` mencegah override tertimpa penilaian yang sedang berjalan; pembaruan bersyarat worker menjaga arah sebaliknya.
- **Jejak.** Hanya override terakhir yang tercatat di baris; riwayat penuh membutuhkan tabel audit (lihat dokumen skema, Bagian 7).

## 7. Keselarasan dengan Dokumen Sebelumnya dan Hal Terbuka

1. **Nama kolom token.** Spesifikasi alur memakai `google_token`, skema memakai `google_access_token`. Perlu satu nama baku.
2. **Status koneksi Google.** Diturunkan dari token NULL. Bila UI perlu menampilkan sejak kapan dan mengapa terputus, tambahkan kolom (misalnya `google_disconnected_at`).
3. **Fallback AI pada short answer.** PRD dan SAD menyebut short answer murni deterministik. Dokumen ini menambah fallback AI per soal (`grading_config.ai_fallback`, usulan default mati karena menambah biaya token); PRD dan SAD perlu diperbarui.
4. **Tahap kedua IdentityFilter.** SAD menggambarkan validasi nilai pada sampel jawaban, dokumen ini memakai Groq untuk item ambigu. Keduanya dapat digabung (nilai sampel setelah sinkronisasi pertama), perlu keputusan.
5. **Parameter `since`.** Google tidak memiliki parameter `since`; yang dipakai `filter` `timestamp >= ...`. `since` adalah nama internal.
6. **Aplikasi OAuth berstatus Testing.** Refresh token kedaluwarsa singkat; sebelum produksi aplikasi harus dipublikasikan dan lolos verifikasi scope (PRD R-09).
7. **Urutan limiter.** Dokumen ini menempatkan limiter tepat sebelum panggilan Groq, bukan di awal job, agar pekerjaan nol-token tidak tertahan.

---

*Akhir dokumen Alur Pengguna dan Alur Teknis v1.0.*
