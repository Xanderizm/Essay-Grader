<?php

namespace App\Support;

class Friendly
{
    public static function error(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') { return ''; }

        $rules = [
            '/Groq error 5\d\d|over capacity/i'                                                => 'Layanan AI sedang sibuk. Coba lagi beberapa menit lagi.',
            '/Semua API key|Groq error 429|rate limit/i'                                       => 'Layanan AI sedang penuh. Sistem akan mencoba lagi otomatis.',
            '/bukan JSON|JSON dari AI|Tidak ada (nilai|klasifikasi) valid|tidak mengembalikan nilai/i' => 'AI memberi jawaban yang tidak bisa dibaca. Coba lagi.',
            '/SERVICE_DISABLED|Google Forms API belum diaktifkan/i'                            => 'Layanan Google Forms belum aktif untuk aplikasi ini. Hubungi pengelola aplikasi.',
            '/Google Forms API membalas status|Gagal memperbarui izin Google/i'                => 'Google sedang bermasalah. Coba lagi nanti.',
            '/Groq error|timed out|cURL|Connection/i'                                          => 'Koneksi ke layanan AI terputus. Coba lagi nanti.',
        ];

        foreach ($rules as $pattern => $message) {
            if (preg_match($pattern, $raw)) { return $message; }
        }

        return mb_substr($raw, 0, 200);
    }
}