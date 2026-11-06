<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function request()
    {
        return view('forgot_password');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));   // hasil diabaikan agar email terdaftar tidak bisa ditebak

        return back()->with('status', 'Jika email terdaftar, tautan atur ulang password sudah dikirim. Periksa kotak masuk dan folder spam.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('reset_password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:10|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password'          => Hash::make($password),
                    'password_set'      => true,
                    'email_verified_at' => $user->email_verified_at ?? now(),   // tautan dikirim ke email ini, jadi terbukti miliknya
                    'remember_token'    => Str::random(60),
                ])->save();

                DB::table('sessions')->where('user_id', $user->id)->delete();   // keluarkan dari semua perangkat
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Password berhasil diubah. Silakan masuk.')
            : back()->withErrors(['email' => 'Tautan tidak valid atau sudah kedaluwarsa. Minta tautan baru.']);
    }
}