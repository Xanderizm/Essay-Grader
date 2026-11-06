<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * "Masuk dengan Google": hanya meminta identitas dasar (openid, email, profile).
 * Terpisah dari GoogleClient, yang meminta izin baca Google Form.
 */
class GoogleLogin
{
    private const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function authUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => config('google.client_id'),
            'redirect_uri'  => config('google.login_redirect_uri'),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'prompt'        => 'select_account',
            'state'         => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** @return array profil Google: sub, email, email_verified, name, given_name, family_name */
    public function profile(string $code): array
    {
        $token = Http::asForm()->post(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => config('google.client_id'),
            'client_secret' => config('google.client_secret'),
            'redirect_uri'  => config('google.login_redirect_uri'),
            'grant_type'    => 'authorization_code',
        ]);

        if ($token->failed()) {
            $why = (string) ($token->json('error_description') ?: $token->json('error') ?: 'tidak diketahui');
            throw new RuntimeException('Google menolak kode otorisasi: ' . mb_substr($why, 0, 160));
        }

        $info = Http::withToken((string) $token->json('access_token'))->get(self::USERINFO_URL);

        if ($info->failed()) {
            throw new RuntimeException('Profil Google tidak bisa dibaca (status ' . $info->status() . ').');
        }

        return (array) $info->json();
    }
}
