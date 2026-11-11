<?php

namespace App\Support;

/** Filter tanpa AI: mengenali judul kolom yang meminta data diri responden. Daftar kata ada di config/identity.php. */
final class IdentityFilter
{
    /**
     * Identitas hanya jika SEMUA kata di judul termasuk daftar identitas atau pelengkap,
     * dan minimal satu kata identitas. Judul soal seperti "Sebutkan nama ibu kota" tidak lolos
     * karena memuat kata di luar daftar. Sisa kasus yang ambigu ditangani lapis kedua (AI).
     */
    public static function isIdentity(string $title): bool
    {
        $words   = array_flip(array_map('mb_strtolower', (array) config('identity.words', [])));
        $fillers = array_flip(array_map('mb_strtolower', (array) config('identity.fillers', [])));

        $text   = preg_replace('/\([^)]*\)|\[[^\]]*\]/u', ' ', mb_strtolower($title));
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', (string) $text, -1, PREG_SPLIT_NO_EMPTY);
        $found  = false;

        foreach ($tokens as $token) {
            if (ctype_digit($token)) { continue; }

            if (isset($words[$token])) { $found = true; continue; }

            if (isset($fillers[$token])) { continue; }

            return false;
        }

        return $found;
    }
}