<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function notice(Request $request)
    {
        return $request->user()->hasVerifiedEmail() ? redirect()->route('dashboard') : view('verify_email');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
            return back()->with('status', 'Email belum bisa dikirim. Coba lagi sebentar lagi.');
        }

        return back()->with('status', 'Tautan aktivasi baru sudah dikirim. Periksa kotak masuk dan folder spam.');
    }

    // Aman dibuka dari perangkat mana pun. Hanya klik pertama yang memverifikasi dan langsung masuk.
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Email ini sudah terverifikasi. Silakan masuk.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Email terverifikasi. Selamat datang!');
    }
}