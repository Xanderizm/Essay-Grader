<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Selalu ke Dashboard, bukan ke halaman yang dituju sebelumnya
            $request->session()->forget('url.intended');

            $restored = $request->user()->cancelPendingDeletion();
            return redirect()->route('dashboard')->with('status', $restored ? 'Selamat datang kembali! Penghapusan akun Anda dibatalkan.' : null);
        }

        return back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->onlyInput('email');
    }

    public function showRegister()
    {
        $this->ensureSignupOpen();

        return view('register');
    }

    public function register(Request $request)
    {
        $this->ensureSignupOpen();

        $rules = [
            'first_name' => 'required|string|max:60',
            'last_name'  => 'nullable|string|max:60',
            'email'      => 'required|email|max:255|unique:users,email',
            'password'   => 'required|string|min:10',
            'agree'      => 'accepted',
        ];

        $invite = config('registration.mode') === 'invite';

        if ($invite) {
            $rules['invite_code'] = 'required|string';
        }

        $data = $request->validate($rules, [
            'first_name.required' => 'Nama depan wajib diisi.',
            'email.required'      => 'Alamat email wajib diisi.',
            'email.email'         => 'Format alamat email tidak valid.',
            'email.unique'        => 'Email ini sudah terdaftar. Silakan masuk.',
            'password.required'   => 'Password wajib diisi.',
            'password.min'        => 'Password minimal 10 karakter.',
            'agree.accepted'      => 'Centang persetujuan Ketentuan Layanan dan Kebijakan Privasi untuk melanjutkan.',
            'invite_code.required' => 'Kode undangan wajib diisi.',
        ]);

        if ($invite && ! hash_equals((string) config('registration.code'), (string) $data['invite_code'])) {
            return back()
                ->withInput($request->only('first_name', 'last_name', 'email', 'agree'))
                ->withErrors(['invite_code' => 'Kode undangan salah.']);
        }

        $user = new User();
        $user->forceFill([
            'name'              => trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')),
            'email'             => strtolower(trim($data['email'])),
            'password'          => Hash::make($data['password']),
            'password_set'      => true,
            'terms_accepted_at' => now(),
        ])->save();

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
            session()->flash('status', 'Email aktivasi belum bisa dikirim. Klik "Kirim ulang email" di halaman ini.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request)
    {
        if ($user = Auth::user()) {
            $activeExamIds = \App\Models\Exam::where('user_id', $user->id)
                ->where('status', 'active')
                ->pluck('id');

            if ($activeExamIds->isNotEmpty()) {
                \App\Models\Exam::whereIn('id', $activeExamIds)->update(['status' => 'closed']);

                \App\Models\StudentAnswer::whereIn('question_id', function ($q) use ($activeExamIds) {
                    $q->select('id')->from('questions')->whereIn('exam_id', $activeExamIds);
                })->where('processing_status', 'processing')->update(['processing_status' => 'pending']);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function ensureSignupOpen(): void
    {
        $mode = config('registration.mode', 'open');

        abort_if($mode === 'closed', 404);
        abort_if($mode === 'invite' && blank(config('registration.code')), 404);
    }
}
