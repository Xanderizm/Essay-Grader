<?php

namespace App\Http\Controllers;

use App\Services\FormAnalyzerService;
use App\Services\GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function __construct(private GoogleClient $google)
    {
    }

    public function index()
    {
        return view('google', ['user' => auth()->user()]);
    }

    public function connect(Request $request)
    {
        if (! config('google.client_id') || ! config('google.client_secret')) {
            return redirect()->route('google.index')
                ->with('status', 'Gagal: koneksi Google belum disiapkan. Hubungi pengelola aplikasi.');
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($this->google->authUrl($state));
    }

    public function callback(Request $request)
    {
        $expected = $request->session()->pull('google_oauth_state');

        if ($request->filled('error')) {
            return redirect()->route('google.index')
                ->with('status', 'Gagal: izin Google tidak diberikan (' . $request->query('error') . ').');
        }

        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('google.index')
                ->with('status', 'Gagal: sesi Anda sudah berakhir. Silakan coba lagi dari awal.');
        }

        try {
            $this->google->connect(auth()->user(), (string) $request->query('code'));
        } catch (\Throwable $e) {
            return redirect()->route('google.index')->with('status', 'Gagal: ' . $e->getMessage());
        }

        return redirect()->route('google.index')->with('status', 'Akun Google berhasil dihubungkan.');
    }

    public function test(Request $request, FormAnalyzerService $analyzer)
    {
        $data = $request->validate(['form_url' => 'required|string|max:500']);

        try {
            $formId = $analyzer->extractFormId($data['form_url']);
            $form   = $this->google->getForm(auth()->user(), $formId);
        } catch (\Throwable $e) {
            return back()->withInput()->with('status', 'Gagal: ' . $e->getMessage());
        }

        $fields = $analyzer->flatten($form);
        $title  = $form['info']['title'] ?? $form['info']['documentTitle'] ?? '(tanpa judul)';

        return back()->with('status', "Berhasil membaca Form \"{$title}\": " . count($fields) . ' jenis isian.');
    }
}