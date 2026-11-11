<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleClient
{
    private const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const REVOKE_URL   = 'https://oauth2.googleapis.com/revoke';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';
    private const FORMS_URL    = 'https://forms.googleapis.com/v1/forms/';

    public const REQUIRED_SCOPES = [
        'https://www.googleapis.com/auth/forms.body.readonly',
        'https://www.googleapis.com/auth/forms.responses.readonly',
    ];

    // ---------- LOGIN GOOGLE (OAuth) ----------

    public function authUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => config('google.client_id'),
            'redirect_uri'  => config('google.redirect_uri'),
            'response_type' => 'code',
            'scope'         => implode(' ', config('google.scopes')),
            'access_type'   => 'offline',   // minta refresh token
            'prompt'        => 'select_account consent',   // tampilkan pilihan akun dan pastikan refresh token diberikan
            'state'         => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function connect(User $user, string $code): void
    {
        $r = Http::asForm()->post(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => config('google.client_id'),
            'client_secret' => config('google.client_secret'),
            'redirect_uri'  => config('google.redirect_uri'),
            'grant_type'    => 'authorization_code',
        ]);

        if ($r->failed()) {
            $why = (string) ($r->json('error_description') ?: $r->json('error') ?: 'tidak diketahui');
            throw new RuntimeException('Google menolak kode otorisasi: ' . mb_substr($why, 0, 200));
        }

        // Pengguna bisa saja menghapus centang sebagian izin di layar persetujuan
        $granted = explode(' ', (string) $r->json('scope'));
        if (array_diff(self::REQUIRED_SCOPES, $granted)) {
            throw new RuntimeException('Izin belum lengkap. Pada layar persetujuan Google, centang SEMUA izin lalu coba lagi.');
        }

        $refresh = $r->json('refresh_token');
        if (! $refresh) {
            throw new RuntimeException('Google tidak memberikan refresh token. Buka myaccount.google.com/permissions, cabut akses aplikasi ini, lalu hubungkan ulang.');
        }

        $access = (string) $r->json('access_token');
        $info   = Http::withToken($access)->get(self::USERINFO_URL);

        $user->forceFill([
            'google_email'            => $info->json('email'),
            'google_token'            => Crypt::encryptString($access),
            'google_refresh_token'    => Crypt::encryptString($refresh),
            'google_token_expires_at' => now()->addSeconds((int) $r->json('expires_in', 3600)),
            'google_connected_at'     => now(),
        ])->save();
    }

    public function disconnect(User $user): void
    {
        try {
            if ($user->google_refresh_token) {
                Http::asForm()->post(self::REVOKE_URL, [
                    'token' => Crypt::decryptString($user->google_refresh_token),
                ]);
            }
        } catch (\Throwable $e) {
            // Gagal mencabut di sisi Google tidak menghalangi pemutusan di sisi kita
        }

        $this->clear($user);
    }

    private function clear(User $user): void
    {
        $user->forceFill([
            'google_email'            => null,
            'google_token'            => null,
            'google_refresh_token'    => null,
            'google_token_expires_at' => null,
            'google_connected_at'     => null,
        ])->save();
    }

    // ---------- TOKEN AKSES (diperbarui otomatis) ----------

    public function accessToken(User $user): string
    {
        if (! $user->google_refresh_token) {
            throw new RuntimeException('Akun Google belum terhubung.', 401);
        }

        if ($user->google_token && $user->google_token_expires_at
            && Carbon::parse($user->google_token_expires_at)->gt(now()->addMinute())) {
            return Crypt::decryptString($user->google_token);
        }

        $r = Http::asForm()->post(self::TOKEN_URL, [
            'client_id'     => config('google.client_id'),
            'client_secret' => config('google.client_secret'),
            'refresh_token' => Crypt::decryptString($user->google_refresh_token),
            'grant_type'    => 'refresh_token',
        ]);

        if ($r->failed()) {
            if ($r->json('error') === 'invalid_grant') {
                $this->clear($user);
                throw new RuntimeException('Izin Google sudah kedaluwarsa atau dicabut. Hubungkan ulang akun Google.', 401);
            }

            throw new RuntimeException('Gagal memperbarui izin Google (status ' . $r->status() . ').', 502);
        }

        $access = (string) $r->json('access_token');

        $user->forceFill([
            'google_token'            => Crypt::encryptString($access),
            'google_token_expires_at' => now()->addSeconds((int) $r->json('expires_in', 3600)),
        ])->save();

        return $access;
    }

    // ---------- GOOGLE FORMS API ----------

    public function getForm(User $user, string $formId): array
    {
        $r = Http::withToken($this->accessToken($user))
            ->acceptJson()
            ->timeout(30)
            ->get(self::FORMS_URL . rawurlencode($formId));

        if ($r->successful()) {
            return (array) $r->json();
        }

        $body = (string) $r->body();

        throw match (true) {
            $r->status() === 401
                => new RuntimeException('Izin Google ditolak. Hubungkan ulang akun Google.', 401),

            $r->status() === 403 && (str_contains($body, 'SERVICE_DISABLED') || str_contains($body, 'has not been used'))
                => new RuntimeException('Layanan Google Forms belum aktif untuk aplikasi ini. Hubungi pengelola aplikasi.', 403),

            $r->status() === 403 && str_contains(strtolower($body), 'insufficient')
                => new RuntimeException('Izin Google kurang lengkap. Hubungkan ulang dan centang semua izin.', 403),

            in_array($r->status(), [403, 404], true)
                => new FormGoneException('Form tidak ditemukan, atau akun Google yang terhubung (' . $user->google_email . ') tidak punya akses ke Form ini.', $r->status()),

            $r->status() === 429
                => new RuntimeException('Terlalu banyak permintaan ke Google. Coba lagi sebentar lagi.', 429),

            default
                => new RuntimeException('Google Forms API membalas status ' . $r->status() . ': ' . mb_substr($body, 0, 200), $r->status()),
        };
    }
    
    /**
     * Ambil jawaban Form. $since = hanya jawaban pada/setelah waktu itu (null = semua).
     */
    public function listResponses(User $user, string $formId, ?Carbon $since = null): array
    {
        $token     = $this->accessToken($user);
        $all       = [];
        $pageToken = null;

        for ($page = 0; $page < 50; $page++) {
            $query = ['pageSize' => 500];

            if ($since) {
                $query['filter'] = 'timestamp >= ' . $since->copy()->utc()->format('Y-m-d\TH:i:s\Z');
            }

            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            $r = Http::withToken($token)
                ->acceptJson()
                ->timeout(60)
                ->get(self::FORMS_URL . rawurlencode($formId) . '/responses', $query);

            if ($r->failed()) {
                $body = (string) $r->body();

                throw match (true) {
                    $r->status() === 401
                        => new RuntimeException('Izin Google ditolak. Hubungkan ulang akun Google.', 401),
                    $r->status() === 429
                        => new RuntimeException('Terlalu banyak permintaan ke Google. Akan dicoba lagi pada penarikan berikutnya.', 429),
                    in_array($r->status(), [403, 404], true)
                        => new FormGoneException('Jawaban Form tidak bisa dibaca: Form tidak ditemukan atau akses ditolak.', $r->status()),
                    default
                        => new RuntimeException('Google Forms API membalas status ' . $r->status() . ': ' . mb_substr($body, 0, 200), $r->status()),
                };
            }

            foreach ((array) $r->json('responses', []) as $response) {
                $all[] = $response;
            }

            $pageToken = $r->json('nextPageToken');

            if (! $pageToken) {
                break;
            }
        }

        return $all;
    }
}