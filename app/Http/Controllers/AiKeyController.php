<?php

namespace App\Http\Controllers;

use App\Models\AiApiKey;
use App\Services\AiGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiKeyController extends Controller
{
    public function index()
    {
        $keys = AiApiKey::orderBy('id')->get()->map(function ($k) {
            $plain = $this->readKey($k);

            return [
                'model'  => $k,
                // Hanya 4 huruf awal dan 4 huruf akhir yang ditampilkan
                'masked' => $plain
                    ? substr($plain, 0, 4) . '••••••••' . substr($plain, -4)
                    : '(tidak terbaca, hapus lalu masukkan ulang)',
            ];
        });

        return view('keys', compact('keys'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'   => 'required|string|max:100',
            'api_key' => 'required|string|min:20|max:300',
        ]);

        AiApiKey::create([
            'provider_name' => 'groq',
            'label'         => trim($data['label']),
            'api_key'       => trim($data['api_key']),
            'is_active'     => true,
        ]);

        return back()->with('status', 'API key disimpan. Klik "Tes" untuk memastikan key berfungsi.');
    }

    public function toggle(AiApiKey $aiKey)
    {
        $aiKey->update([
            'is_active'      => ! $aiKey->is_active,
            'cooldown_until' => null,
        ]);

        return back()->with('status', 'Status key diperbarui.');
    }

    public function destroy(AiApiKey $aiKey)
    {
        $aiKey->delete();

        return back()->with('status', 'API key dihapus.');
    }

    public function test(AiApiKey $aiKey)
    {
        $plain = $this->readKey($aiKey);

        if (! $plain) {
            return back()->with('status', 'Key tidak bisa dibaca. Hapus lalu masukkan ulang.');
        }

        try {
            $r = Http::withToken($plain)->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'                 => AiGradingService::MODELS[0],
                'messages'              => [['role' => 'user', 'content' => 'Balas dengan satu kata: halo']],
                'max_completion_tokens' => 200,
            ]);
        } catch (\Throwable $e) {
            return back()->with('status', 'Tidak bisa terhubung ke Groq. Periksa koneksi internet.');
        }

        $message = match (true) {
            $r->status() === 200 => 'Key "' . $aiKey->label . '" berfungsi.',
            $r->status() === 401 => 'Key "' . $aiKey->label . '" ditolak Groq (401): key salah atau sudah dicabut.',
            $r->status() === 429 => 'Key "' . $aiKey->label . '" sedang kena limit (429). Coba lagi beberapa menit lagi.',
            default              => 'Groq membalas status ' . $r->status() . '. Kalau 503, server Groq sedang penuh; coba lagi nanti.',
        };

        return back()->with('status', $message);
    }

    private function readKey(AiApiKey $k): ?string
    {
        try {
            return $k->api_key;
        } catch (\Throwable $e) {
            return null;
        }
    }
}