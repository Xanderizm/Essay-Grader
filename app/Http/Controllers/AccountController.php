<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\User;
use App\Services\GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function show()
    {
        return view('account', [
            'user'      => auth()->user(),
            'examCount' => Exam::where('user_id', auth()->id())->count(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $rules = ['password' => 'required|string|min:10|confirmed'];

        // Akun yang dibuat lewat Google belum punya password lama
        if ($user->password_set) {
            $rules['current_password'] = 'required';
        }

        $data = $request->validate($rules);

        if ($user->password_set && ! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama salah.']);
        }

        $user->forceFill([
            'password'     => Hash::make($data['password']),
            'password_set' => true,
        ])->save();

        return back()->with('status', 'Password diperbarui.');
    }

    public function destroy(Request $request, GoogleClient $google)
    {
        $user = $request->user();

        if ($user->password_set) {
            $request->validate(['password' => 'required']);

            if (! Hash::check($request->input('password'), $user->password)) {
                return back()->withErrors(['password' => 'Password salah.']);
            }
        } else {
            // Akun Google tanpa password: konfirmasi dengan mengetik ulang email akun
            $request->validate(['confirm_email' => 'required|email']);

            if (strcasecmp((string) $request->input('confirm_email'), (string) $user->email) !== 0) {
                return back()->withErrors(['confirm_email' => 'Email konfirmasi tidak cocok dengan email akun.']);
            }
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->withErrors(['password' => 'Admin terakhir tidak bisa dihapus. Jadikan akun lain admin dulu.']);
        }

        if ($request->boolean('immediate')) {
            $user->notifyNow(new \App\Notifications\AccountDeletionNotice(null));   // dikirim sebelum data hilang
            $google->disconnect($user);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $user->delete();   // ujian, jawaban, nilai, dan kunci AI ikut terhapus (cascade)

            return redirect('/')->with('status', 'Akun dan seluruh datanya telah dihapus.');
        }

        $purgeAt = now()->addDays(14);
        $user->forceFill(['deletion_requested_at' => now()])->save();
        $user->notify(new \App\Notifications\AccountDeletionNotice($purgeAt));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Akun dijadwalkan dihapus pada ' . $purgeAt->timezone('Asia/Jakarta')->translatedFormat('d F Y') . '. Masuk kembali sebelum tanggal itu untuk membatalkan.');
    }

    public function export(Request $request)
    {
        $user = $request->user();

        return response()->streamDownload(function () use ($user) {
            $out  = fopen('php://output', 'w');
            $json = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            fwrite($out, '{"akun":' . $json(['nama' => $user->name, 'email' => $user->email, 'dibuat' => (string) $user->created_at]) . ',"penilaian":[');

            $firstExam = true;
            foreach (Exam::where('user_id', $user->id)->with('questions')->get() as $exam) {
                fwrite($out, ($firstExam ? '' : ',') . '{"judul":' . $json($exam->title)
                    . ',"pertanyaan":' . $json($exam->questions->map(fn ($q) => ['judul' => $q->question_text, 'jenis' => $q->gform_item_type, 'peran' => $q->field_type, 'rubrik' => $q->answer_key])->values())
                    . ',"responden":[');
                $firstExam = false;

                $first = true;
                $exam->submissions()->with('answers')->chunkById(200, function ($subs) use ($out, $json, &$first) {
                    foreach ($subs as $s) {
                        fwrite($out, ($first ? '' : ',') . $json([
                            'identitas' => $s->identity,
                            'dikirim'   => (string) $s->submitted_at,
                            'jawaban'   => $s->answers->map(fn ($a) => ['isi' => $a->raw_answer, 'nilai' => $a->final_score, 'kategori' => $a->ai_category, 'alasan' => $a->ai_analysis])->values(),
                        ]));
                        $first = false;
                    }
                });
                fwrite($out, ']}');
            }

            fwrite($out, ']}');
            fclose($out);
        }, 'data-akun-' . date('Ymd') . '.json', ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
