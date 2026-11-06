<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function __construct(private GoogleLogin $google)
    {
    }

    // Dipanggil dengan POST dari tombol Google pada halaman Masuk atau Daftar
    public function redirect(Request $request)
    {
        $intent = $request->input('intent') === 'register' ? 'register' : 'login';
        $back   = $intent === 'register' ? 'register' : 'login';

        if (! config('google.client_id') || ! config('google.client_secret')) {
            return redirect()->route($back)->withErrors([
                'google' => 'Masuk dengan Google belum tersedia. Hubungi pengelola aplikasi.',
            ]);
        }

        $mode     = config('registration.mode', 'open');
        $signupOk = $mode === 'open';   // Google proceeds directly; no checkbox validation

        if ($mode === 'invite' && $intent === 'register' && filled(config('registration.code'))) {
            $request->validate(['invite_code' => 'required|string'], ['invite_code.required' => 'Kode undangan wajib diisi.']);
            if (! hash_equals((string) config('registration.code'), (string) $request->input('invite_code'))) {
                return back()->withInput($request->only('first_name', 'last_name', 'email'))
                    ->withErrors(['invite_code' => 'Kode undangan salah.']);
            }
            $signupOk = true;
        }

        $state = Str::random(40);

        $request->session()->put('google_auth', [
            'state'     => $state,
            'intent'    => $intent,
            'signup_ok' => $signupOk,
        ]);

        return redirect()->away($this->google->authUrl($state));
    }

    public function callback(Request $request)
    {
        $flow     = $request->session()->pull('google_auth');
        $fallback = ($flow['intent'] ?? 'login') === 'register' ? 'register' : 'login';

        if ($request->filled('error')) {
            return $this->fail($fallback, 'Masuk dengan Google dibatalkan atau ditolak.');
        }

        if (! $flow || ! hash_equals((string) $flow['state'], (string) $request->query('state'))) {
            return $this->fail($fallback, 'Sesi Anda sudah berakhir. Silakan coba lagi dari awal.');
        }

        try {
            $profile = $this->google->profile((string) $request->query('code'));
        } catch (\Throwable $e) {
            return $this->fail($fallback, $e->getMessage());
        }

        if (empty($profile['email']) || empty($profile['email_verified'])) {
            return $this->fail($fallback, 'Alamat email akun Google ini belum terverifikasi.');
        }

        $email = strtolower((string) $profile['email']);
        $user  = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $notice = null;

        if ($user) {
            // Akun password yang emailnya belum terbukti milik pendaftarnya: ambil alih secara aman.
            // Password lama dinonaktifkan agar pihak yang mendaftarkan email orang lain tidak bisa masuk.
            if ($user->email_verified_at === null) {
                $user->forceFill([
                    'email_verified_at' => now(),
                    'password'          => Hash::make(Str::random(40)),
                    'password_set'      => false,
                ])->save();

                $notice = 'Akun ini kini tertaut dengan Google dan password lama dinonaktifkan. Buat password baru di menu Akun jika masih ingin masuk dengan password.';
            }
        } else {
            if (empty($flow['signup_ok'])) {
                return redirect()->route('register')->withErrors([
                    'google' => 'Pendaftaran akun baru sedang dibatasi. Hubungi pengelola.',
                ]);
            }

            $name = trim((string) ($profile['name'] ?? ''));

            if ($name === '') {
                $name = trim(($profile['given_name'] ?? '') . ' ' . ($profile['family_name'] ?? ''));
            }

            $user = new User();
            $user->forceFill([
                'name'              => $name !== '' ? mb_substr($name, 0, 120) : Str::before($email, '@'),
                'email'             => $email,
                'password'          => Hash::make(Str::random(40)),
                'password_set'      => false,
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
            ])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        $notice = $user->cancelPendingDeletion() ? 'Selamat datang kembali! Penghapusan akun Anda dibatalkan.' : $notice;
        return redirect()->route('dashboard')->with('status', $notice);
    }

    private function fail(string $route, string $message)
    {
        return redirect()->route($route)->withErrors(['google' => $message]);
    }
}
